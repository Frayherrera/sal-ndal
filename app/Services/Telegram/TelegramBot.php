<?php

namespace App\Services\Telegram;

use App\Services\Telegram\IA\AsistenteTelegram;
use App\Services\Telegram\IA\LimiteIAException;
use App\Services\Telegram\TelegramFormato as F;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * Procesa los "updates" que Telegram envía al webhook: mensajes con comandos
 * y pulsaciones de los botones de confirmación.
 */
class TelegramBot
{
    /** Minutos que una solicitud de registro espera confirmación. */
    private const MINUTOS_CONFIRMACION = 15;

    /** Menú que Telegram muestra al escribir "/". */
    public const COMANDOS = [
        'resumen' => 'Ventas, gastos y cartera de hoy y del mes',
        'cartera' => 'Saldos pendientes (o de un cliente: /cartera nombre)',
        'stock' => 'Stock de un artículo: /stock comino',
        'alertas' => 'Artículos con stock bajo',
        'ventas' => 'Últimas ventas',
        'gastos' => 'Últimos gastos',
        'camion' => 'Dónde está el camión y qué lleva',
        'vendido' => 'Lo vendido en el último viaje (o /vendido 3)',
        'semanas' => 'Vendido por semana (o /semanas comino)',
        'rutas' => 'Rutas del camión en orden de rotación',
        'ruta' => 'Crear ruta: /ruta Gamarra | Gamarra, Aguachica',
        'gasto' => 'Registrar gasto: /gasto 70000 comida | Ruta',
        'venta' => 'Registrar venta: /venta Cliente 230000 [credito]',
        'abono' => 'Registrar abono: /abono Cliente 50000 [nequi]',
        'ayuda' => 'Ver todos los comandos',
    ];

    public function __construct(
        private TelegramClient $telegram,
        private TelegramConsultas $consultas,
        private TelegramRegistros $registros,
        private AsistenteTelegram $asistente,
        private TelegramCamion $camion,
    ) {}

    /**
     * Mensajes sin "/" van al asistente con IA (si hay GEMINI_API_KEY).
     * Devuelve el texto a enviar (ya escapado para HTML).
     */
    private function responderConIA(int|string $chatId, string $texto): string
    {
        if (! $this->asistente->disponible()) {
            return 'No entendí el mensaje. Escribe /ayuda para ver los comandos.';
        }

        $this->telegram->escribiendo($chatId);

        try {
            $respuesta = $this->asistente->responder(
                $chatId,
                $texto,
                fn (array $preparado) => $this->pedirConfirmacion($chatId, $preparado),
            );
        } catch (LimiteIAException) {
            return '⏳ El asistente alcanzó su límite gratuito por ahora. Mientras tanto usa los comandos: /ayuda';
        }

        return F::e($respuesta);
    }

    /**
     * @param  array<string, mixed>  $update
     */
    public function procesar(array $update): void
    {
        if (isset($update['callback_query'])) {
            $this->procesarBoton($update['callback_query']);

            return;
        }

        $mensaje = $update['message'] ?? null;
        $chatId = $mensaje['chat']['id'] ?? null;
        $texto = trim((string) ($mensaje['text'] ?? ''));

        if ($chatId === null) {
            return; // Otros tipos de update: se ignoran
        }

        if (! $this->autorizado($chatId)) {
            $this->telegram->enviarMensaje($chatId,
                "⛔ Este chat no está autorizado para usar el bot de Santini.\n"
                ."Tu ID de chat es <code>{$chatId}</code>: pídele al administrador que lo agregue a TELEGRAM_ALLOWED_CHAT_IDS.");

            return;
        }

        if ($texto === '') {
            return;
        }

        $this->responderComando($chatId, $texto);
    }

    public function autorizado(int|string $chatId): bool
    {
        return in_array((string) $chatId, config('services.telegram.allowed_chat_ids', []), true);
    }

    private function responderComando(int|string $chatId, string $texto): void
    {
        [$comando, $argumentos] = $this->separarComando($texto);

        try {
            $respuesta = match ($comando) {
                'start', 'ayuda', 'help' => $this->ayuda(),
                'resumen' => $this->consultas->resumen(),
                'cartera' => $this->consultas->cartera($argumentos),
                'stock' => $this->consultas->stock($argumentos),
                'alertas' => $this->consultas->alertas(),
                'ventas' => $this->consultas->ventas((int) ($argumentos ?: 10)),
                'gastos' => $this->consultas->gastos((int) ($argumentos ?: 10)),
                'camion' => $this->camion->estado(),
                'vendido' => $this->camion->vendido($argumentos),
                'semanas' => $this->camion->semanas($argumentos),
                'rutas' => $this->camion->rutas(),
                'ruta' => $this->pedirConfirmacion($chatId, $this->registros->prepararRuta($argumentos)),
                'gasto' => $this->pedirConfirmacion($chatId, $this->registros->prepararGasto($argumentos)),
                'venta' => $this->pedirConfirmacion($chatId, $this->registros->prepararVenta($argumentos)),
                'abono' => $this->pedirConfirmacion($chatId, $this->registros->prepararAbono($argumentos)),
                null => $this->responderConIA($chatId, $texto),
                default => 'Comando desconocido: /'.F::e($comando).'. Escribe /ayuda para ver los comandos.',
            };
        } catch (RuntimeException $e) {
            // Errores de validación: el mensaje ya es apto para el usuario
            $respuesta = '⚠️ '.$e->getMessage();
        } catch (Throwable $e) {
            Log::error('Telegram: error procesando comando', ['comando' => $comando, 'error' => $e->getMessage()]);
            $respuesta = '❌ Ocurrió un error al procesar la solicitud. Inténtalo de nuevo.';
        }

        if ($respuesta !== null) {
            $this->telegram->enviarMensaje($chatId, F::limitar($respuesta));
        }
    }

    /**
     * Guarda la acción preparada y muestra el resumen con botones Confirmar / Cancelar.
     * Devuelve null porque el mensaje ya se envió aquí.
     *
     * @param  array{resumen: string, accion: array<string, mixed>}  $preparado
     */
    private function pedirConfirmacion(int|string $chatId, array $preparado): ?string
    {
        $token = Str::random(16);

        Cache::put($this->claveCache($token), [
            'chat_id' => (string) $chatId,
            'accion' => $preparado['accion'],
        ], now()->addMinutes(self::MINUTOS_CONFIRMACION));

        $this->telegram->enviarMensaje($chatId, $preparado['resumen']."\n\n¿Confirmas el registro?", [
            'reply_markup' => [
                'inline_keyboard' => [[
                    ['text' => '✅ Confirmar', 'callback_data' => "si:{$token}"],
                    ['text' => '❌ Cancelar', 'callback_data' => "no:{$token}"],
                ]],
            ],
        ]);

        return null;
    }

    /**
     * @param  array<string, mixed>  $callback
     */
    private function procesarBoton(array $callback): void
    {
        $chatId = $callback['message']['chat']['id'] ?? null;
        $messageId = $callback['message']['message_id'] ?? null;
        [$decision, $token] = array_pad(explode(':', (string) ($callback['data'] ?? ''), 2), 2, '');

        if ($chatId === null || $messageId === null || ! $this->autorizado($chatId)) {
            $this->telegram->responderCallback($callback['id'], 'No autorizado.');

            return;
        }

        $pendiente = Cache::pull($this->claveCache($token));

        if (! $pendiente || $pendiente['chat_id'] !== (string) $chatId) {
            $this->telegram->responderCallback($callback['id'], 'Esta solicitud ya expiró o fue atendida.');
            $this->telegram->editarMensaje($chatId, $messageId, '⌛ Solicitud vencida. Envía el comando de nuevo.');

            return;
        }

        if ($decision !== 'si') {
            $this->telegram->responderCallback($callback['id'], 'Cancelado');
            $this->telegram->editarMensaje($chatId, $messageId, '❌ Registro cancelado. No se guardó nada.');

            return;
        }

        try {
            $resultado = $this->registros->ejecutar($pendiente['accion']);
            $this->telegram->responderCallback($callback['id'], 'Registrado');
        } catch (RuntimeException $e) {
            $resultado = '⚠️ No se pudo registrar: '.$e->getMessage();
            $this->telegram->responderCallback($callback['id'], 'No se pudo registrar');
        } catch (Throwable $e) {
            Log::error('Telegram: error ejecutando registro', ['accion' => $pendiente['accion'], 'error' => $e->getMessage()]);
            $resultado = '❌ Ocurrió un error al registrar. No se guardó nada.';
            $this->telegram->responderCallback($callback['id'], 'Error');
        }

        $this->telegram->editarMensaje($chatId, $messageId, F::limitar($resultado));
    }

    /**
     * "/venta@SantiniBot Juan 1000" → ['venta', 'Juan 1000']
     *
     * @return array{0: ?string, 1: string}
     */
    private function separarComando(string $texto): array
    {
        if (! preg_match('/^\/([a-záéíóúñ_]+)(?:@\S+)?(?:\s+(.*))?$/isu', $texto, $m)) {
            return [null, $texto];
        }

        return [F::normalizar($m[1]), trim($m[2] ?? '')];
    }

    private function claveCache(string $token): string
    {
        return 'telegram:confirmacion:'.$token;
    }

    private function ayuda(): string
    {
        return implode("\n", [
            '<b>🤖 Bot de Santini</b>',
            '',
            '<b>Consultas</b>',
            '/resumen — ventas, gastos y cartera de hoy y del mes',
            '/cartera — saldos pendientes por cliente',
            '/cartera <i>cliente</i> — ventas pendientes de un cliente',
            '/stock <i>texto</i> — stock de materias primas y productos',
            '/alertas — artículos con stock bajo',
            '/ventas [n] — últimas ventas',
            '/gastos [n] — últimos gastos',
            '',
            '<b>Camión</b>',
            '/camion — dónde está y qué lleva',
            '/vendido — lo vendido en el último viaje (o <code>/vendido 3</code> para el VJE-00003)',
            '/semanas — vendido por viaje; <code>/semanas comino</code> para un producto',
            '/rutas — rutas en orden de rotación',
            '<code>/ruta Gamarra | Gamarra, Aguachica</code> — crear ruta (pide confirmación)',
            '   el cargue y el regreso se registran en la app web',
            '',
            '<b>Registros</b> (piden confirmación)',
            '<code>/gasto 70000 comida | Ruta</code>',
            '   valor, descripción y, después de «|», la categoría (por defecto Otros)',
            '<code>/venta JESUS DUQUE 230000</code>',
            '   agrega <i>credito</i> al final para dejarla en cartera (vence a 30 días)',
            '   y «| nota» para observaciones',
            '<code>/abono YURANI CHINCHILLA 50000 nequi</code>',
            '   se aplica a las ventas pendientes más antiguas del cliente',
            '',
            'Los valores aceptan 70000, 70.000 o 70k. Los nombres no distinguen mayúsculas ni tildes.',
        ]);
    }
}

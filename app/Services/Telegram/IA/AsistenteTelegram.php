<?php

namespace App\Services\Telegram\IA;

use App\Models\PagoVenta;
use App\Services\Telegram\TelegramCamion;
use App\Services\Telegram\TelegramConsultas;
use App\Services\Telegram\TelegramRegistros;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

/**
 * Asistente con IA para los mensajes en lenguaje natural del bot.
 *
 * El modelo entiende la intención y llama a funciones que son las mismas consultas
 * y preparaciones de registro del bot. Las funciones de registro solo PREPARAN:
 * el bot muestra el resumen con botones y nada se guarda hasta que el usuario confirma.
 */
class AsistenteTelegram
{
    /** Máximo de idas y vueltas con el modelo por mensaje (evita ciclos infinitos). */
    private const MAX_VUELTAS = 6;

    /** Intercambios recientes que se recuerdan por chat, y por cuánto tiempo. */
    private const MAX_HISTORIAL = 6;

    private const MINUTOS_HISTORIAL = 60;

    public function __construct(
        private ModeloIA $modelo,
        private TelegramConsultas $consultas,
        private TelegramRegistros $registros,
        private TelegramCamion $camion,
    ) {}

    public function disponible(): bool
    {
        return $this->modelo->configurado();
    }

    /**
     * Responde un mensaje en lenguaje natural. Devuelve el texto final (plano, sin HTML).
     *
     * @param  callable(array{resumen: string, accion: array<string, mixed>}): mixed  $pedirConfirmacion
     *                                                                                  muestra el resumen con botones ✅/❌
     */
    public function responder(int|string $chatId, string $mensaje, callable $pedirConfirmacion): string
    {
        $conversacion = [[
            'role' => 'user',
            'parts' => [['text' => $this->contextoReciente($chatId).$mensaje]],
        ]];

        $respuesta = null;

        for ($vuelta = 0; $vuelta < self::MAX_VUELTAS; $vuelta++) {
            $respuesta = $this->modelo->responder($this->instrucciones(), $this->funciones(), $conversacion);

            if ($respuesta->llamadas === []) {
                break;
            }

            // Se devuelve el turno del modelo sin cambios y luego los resultados de sus funciones
            $conversacion[] = $respuesta->contenido;
            $conversacion[] = [
                'role' => 'user',
                'parts' => array_map(fn (array $llamada) => [
                    'functionResponse' => [
                        'name' => $llamada['name'],
                        'response' => $this->ejecutar($llamada['name'], $llamada['args'], $pedirConfirmacion),
                    ],
                ], $respuesta->llamadas),
            ];
        }

        $texto = $respuesta?->texto ?? '';

        if ($texto === '') {
            $texto = $respuesta && $respuesta->llamadas !== []
                ? 'No logré completar la solicitud. Intenta decirlo de otra forma o usa /ayuda.'
                : 'No te entendí. Intenta decirlo de otra forma o escribe /ayuda para ver los comandos.';
        }

        $this->recordar($chatId, $mensaje, $texto);

        return $texto;
    }

    /**
     * Ejecuta una función pedida por el modelo y devuelve su resultado (texto plano).
     *
     * @param  array<string, mixed>  $args
     * @return array<string, string>
     */
    private function ejecutar(string $nombre, array $args, callable $pedirConfirmacion): array
    {
        $texto = fn (string $clave) => trim((string) ($args[$clave] ?? ''));
        $numero = fn (string $clave) => (float) ($args[$clave] ?? 0);

        try {
            $resultado = match ($nombre) {
                'resumen' => $this->consultas->resumen(),
                'cartera' => $this->consultas->cartera($texto('cliente')),
                'stock' => $this->consultas->stock($texto('texto')),
                'alertas_stock' => $this->consultas->alertas(),
                'ultimas_ventas' => $this->consultas->ventas((int) ($args['cantidad'] ?? 10)),
                'ultimos_gastos' => $this->consultas->gastos((int) ($args['cantidad'] ?? 10)),
                'estado_camion' => $this->camion->estado(),
                'vendido_viaje' => $this->camion->vendido($texto('viaje')),
                'vendido_por_semana' => $this->camion->semanas($texto('producto'), (int) ($args['cantidad'] ?? 6)),
                'listar_rutas' => $this->camion->rutas(),
                'preparar_ruta' => $this->confirmar($pedirConfirmacion, $this->registros->prepararRutaDatos(
                    $texto('nombre'),
                    $texto('ciudades') ?: null,
                    isset($args['orden']) ? (int) $args['orden'] : null,
                )),
                'preparar_gasto' => $this->confirmar($pedirConfirmacion, $this->registros->prepararGastoDatos(
                    $numero('valor'), $texto('descripcion'), $texto('categoria') ?: null
                )),
                'preparar_venta' => $this->confirmar($pedirConfirmacion, $this->registros->prepararVentaDatos(
                    $texto('cliente'), $numero('valor'), $texto('forma_pago') ?: 'contado', $texto('nota') ?: null
                )),
                'preparar_abono' => $this->confirmar($pedirConfirmacion, $this->registros->prepararAbonoDatos(
                    $texto('cliente'), $numero('valor'), $texto('metodo') ?: 'efectivo'
                )),
                default => throw new RuntimeException("Función desconocida: {$nombre}"),
            };

            return ['resultado' => $this->plano($resultado)];
        } catch (RuntimeException $e) {
            // Errores de validación (cliente ambiguo, valor inválido…): el modelo se los explica al usuario
            return ['error' => $this->plano($e->getMessage())];
        }
    }

    /**
     * @param  array{resumen: string, accion: array<string, mixed>}  $preparado
     */
    private function confirmar(callable $pedirConfirmacion, array $preparado): string
    {
        $pedirConfirmacion($preparado);

        return "Se le mostró al usuario este resumen con botones Confirmar / Cancelar. TODAVÍA NO ESTÁ GUARDADO: "
            ."se guarda solo si el usuario pulsa Confirmar.\n".$preparado['resumen'];
    }

    private function plano(string $html): string
    {
        return trim(html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8'));
    }

    private function instrucciones(): string
    {
        return implode("\n", [
            'Eres el asistente de Santini, una comercializadora de condimentos y especias en Colombia.',
            'Hablas por Telegram con el dueño del negocio. Responde siempre en español, breve y claro.',
            'Hoy es '.now()->locale('es')->translatedFormat('l j \d\e F \d\e Y').'. Los valores son pesos colombianos (COP).',
            '',
            'Reglas:',
            '- Para cualquier dato del negocio usa las funciones. Nunca inventes cifras, clientes ni saldos.',
            '- Para registrar un gasto, una venta, un abono o crear una ruta del camión usa las funciones preparar_*. Esas funciones NO guardan:',
            '  le muestran al usuario un resumen con botones y se guarda solo si él pulsa Confirmar.',
            '  Después de prepararlo, dile en una frase que revise el resumen y lo confirme. Nunca digas que ya quedó guardado.',
            '- Convierte cantidades escritas en palabras a números: "70 mil" = 70000, "1,5 millones" = 1500000. Si un valor parece incompleto o ambiguo (ej. "230"), pregunta antes de preparar el registro.',
            '- Una venta es de contado salvo que digan crédito, fiado o "me queda debiendo".',
            '- Si falta un dato obligatorio (cliente, valor, descripción) o una función devuelve error, pregunta o explica en una frase.',
            '- El camión hace un viaje por semana a una ruta fija. Lo que se vende por producto sale de las funciones del camión, en paquetes de 20 unidades ("p") y unidades sueltas ("u"). El cargue y el regreso del camión no se registran por aquí: se hacen en la aplicación web.',
            '- Si el usuario pide algo que ninguna función hace, dilo y sugiere escribir /ayuda.',
            '- Responde en texto plano: sin Markdown, sin asteriscos ni tablas. Puedes usar saltos de línea.',
        ]);
    }

    /**
     * Declaraciones de funciones para Gemini (esquema OpenAPI).
     *
     * @return array<int, array<string, mixed>>
     */
    private function funciones(): array
    {
        $texto = fn (string $descripcion) => ['type' => 'STRING', 'description' => $descripcion];
        $valor = ['type' => 'NUMBER', 'description' => 'Valor en pesos colombianos, como número (ej. 70000).'];
        $cantidad = ['type' => 'INTEGER', 'description' => 'Cuántos mostrar (1 a 30).'];

        return [
            ['name' => 'resumen', 'description' => 'Ventas, gastos y cartera de hoy y del mes en curso.'],
            ['name' => 'cartera', 'description' => 'Saldos pendientes. Sin cliente: total y deudores. Con cliente: sus ventas pendientes.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['cliente' => $texto('Nombre del cliente o de su local (opcional).')]]],
            ['name' => 'stock', 'description' => 'Stock actual de materias primas y productos cuyo nombre contiene un texto.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['texto' => $texto('Parte del nombre, ej. "comino".')], 'required' => ['texto']]],
            ['name' => 'alertas_stock', 'description' => 'Materias primas y productos con stock bajo.'],
            ['name' => 'ultimas_ventas', 'description' => 'Últimas ventas registradas.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['cantidad' => $cantidad]]],
            ['name' => 'ultimos_gastos', 'description' => 'Últimos gastos registrados y total gastado en el mes.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['cantidad' => $cantidad]]],
            ['name' => 'estado_camion', 'description' => 'Dónde está el camión de reparto (en ruta o en casa), qué ruta hace y qué productos lleva o le quedan.'],
            ['name' => 'vendido_viaje', 'description' => 'Lo vendido por producto en un viaje cerrado del camión (por defecto el último), con mermas, ventas registradas, gastos de ruta y resultado.',
                'parameters' => ['type' => 'OBJECT', 'properties' => ['viaje' => $texto('Número del viaje, ej. "VJE-00003" o "3" (opcional; por defecto el último cerrado).')]]],
            ['name' => 'vendido_por_semana', 'description' => 'Lo vendido por el camión en los últimos viajes (uno por semana). Sin producto: total por viaje. Con producto: ese producto viaje por viaje.',
                'parameters' => ['type' => 'OBJECT', 'properties' => [
                    'producto' => $texto('Parte del nombre de un producto, ej. "comino" (opcional).'),
                    'cantidad' => ['type' => 'INTEGER', 'description' => 'Cuántos viajes recientes (1 a 12, por defecto 6).'],
                ]]],
            ['name' => 'listar_rutas', 'description' => 'Rutas fijas del camión en el orden en que se rotan cada semana, con sus ciudades y cuántos viajes tienen.'],
            ['name' => 'preparar_ruta', 'description' => 'Prepara la creación de una ruta nueva del camión y le pide confirmación al usuario. No guarda. Por defecto va al final de la rotación.',
                'parameters' => ['type' => 'OBJECT', 'properties' => [
                    'nombre' => $texto('Nombre de la ruta, ej. "Gamarra".'),
                    'ciudades' => $texto('Ciudades que visita, separadas por coma, ej. "Gamarra, Aguachica" (opcional).'),
                    'orden' => ['type' => 'INTEGER', 'description' => 'Posición en la rotación (opcional; por defecto al final).'],
                ], 'required' => ['nombre']]],
            ['name' => 'preparar_gasto', 'description' => 'Prepara el registro de un gasto y le pide confirmación al usuario. No guarda.',
                'parameters' => ['type' => 'OBJECT', 'properties' => [
                    'valor' => $valor,
                    'descripcion' => $texto('En qué se gastó, ej. "Almuerzo".'),
                    'categoria' => $texto('Categoría del gasto, ej. "Ruta", "Santini", "Arriendo" (opcional; por defecto Otros).'),
                ], 'required' => ['valor', 'descripcion']]],
            ['name' => 'preparar_venta', 'description' => 'Prepara el registro de una venta y le pide confirmación al usuario. No guarda.',
                'parameters' => ['type' => 'OBJECT', 'properties' => [
                    'cliente' => $texto('Nombre del cliente o de su local.'),
                    'valor' => $valor,
                    'forma_pago' => ['type' => 'STRING', 'enum' => ['contado', 'credito'], 'description' => 'contado (pagada) o credito (queda en cartera).'],
                    'nota' => $texto('Observación opcional, ej. qué se vendió.'),
                ], 'required' => ['cliente', 'valor', 'forma_pago']]],
            ['name' => 'preparar_abono', 'description' => 'Prepara un abono de un cliente a sus ventas pendientes y pide confirmación. No guarda.',
                'parameters' => ['type' => 'OBJECT', 'properties' => [
                    'cliente' => $texto('Nombre del cliente o de su local.'),
                    'valor' => $valor,
                    'metodo' => ['type' => 'STRING', 'enum' => array_keys(PagoVenta::METODOS), 'description' => 'Método de pago (por defecto efectivo).'],
                ], 'required' => ['cliente', 'valor']]],
        ];
    }

    /** Últimos intercambios del chat, como texto, para entender preguntas de seguimiento. */
    private function contextoReciente(int|string $chatId): string
    {
        $historial = Cache::get($this->claveHistorial($chatId), []);

        if ($historial === []) {
            return '';
        }

        $lineas = array_map(fn (array $par) => "Usuario: {$par[0]}\nAsistente: {$par[1]}", $historial);

        return "Conversación reciente (solo como contexto):\n".implode("\n", $lineas)."\n\nMensaje actual del usuario:\n";
    }

    private function recordar(int|string $chatId, string $mensaje, string $respuesta): void
    {
        $historial = Cache::get($this->claveHistorial($chatId), []);
        $historial[] = [mb_substr($mensaje, 0, 500), mb_substr($respuesta, 0, 800)];

        Cache::put(
            $this->claveHistorial($chatId),
            array_slice($historial, -self::MAX_HISTORIAL),
            now()->addMinutes(self::MINUTOS_HISTORIAL)
        );
    }

    private function claveHistorial(int|string $chatId): string
    {
        return 'telegram:ia:historial:'.$chatId;
    }
}

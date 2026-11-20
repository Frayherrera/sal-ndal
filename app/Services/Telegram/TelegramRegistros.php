<?php

namespace App\Services\Telegram;

use App\Models\Cliente;
use App\Models\PagoVenta;
use App\Models\User;
use App\Models\Venta;
use App\Services\GastoService;
use App\Services\PagoVentaService;
use App\Services\Telegram\TelegramFormato as F;
use App\Services\VentaService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Registros que se hacen desde el bot (gasto, venta, abono).
 *
 * Cada registro tiene dos pasos:
 *  - preparar*(): interpreta el texto y devuelve ['resumen' => html, 'accion' => datos].
 *    No guarda nada; el bot muestra el resumen con botones Confirmar / Cancelar.
 *  - ejecutar(): guarda la acción confirmada usando los servicios de la app,
 *    que vuelven a validar todo (el estado pudo cambiar mientras tanto).
 */
class TelegramRegistros
{
    private const FORMAS = ['contado' => Venta::FORMA_CONTADO, 'credito' => Venta::FORMA_CREDITO];

    public function __construct(
        private BuscadorTelegram $buscador,
        private TelegramConsultas $consultas,
        private GastoService $gastos,
        private VentaService $ventas,
        private PagoVentaService $pagos,
    ) {}

    /**
     * /gasto <valor> <descripción> [| categoría]
     *
     * @return array{resumen: string, accion: array<string, mixed>}
     */
    public function prepararGasto(string $texto): array
    {
        [$principal, $categoriaTexto] = $this->separar($texto);
        [$monto, $descripcion] = $this->extraerMonto($principal, 'el valor del gasto');

        if ($descripcion === '') {
            throw new RuntimeException('Falta la descripción. Ej: <code>/gasto 70000 comida | Ruta</code>');
        }

        $categoria = $this->buscador->categoria($categoriaTexto !== '' ? $categoriaTexto : 'Otros');
        $descripcion = mb_strtoupper(mb_substr($descripcion, 0, 1)).mb_substr($descripcion, 1);

        return [
            'resumen' => implode("\n", [
                '<b>💸 Registrar gasto</b>',
                'Valor: <b>'.F::dinero($monto).'</b>',
                'Descripción: '.F::e($descripcion),
                'Categoría: '.F::e($categoria->nombre),
                'Fecha: '.now()->format('d/m/Y'),
            ]),
            'accion' => [
                'tipo' => 'gasto',
                'monto' => $monto,
                'descripcion' => mb_substr($descripcion, 0, 255),
                'categoria_gasto_id' => $categoria->id,
            ],
        ];
    }

    /**
     * /venta <cliente> <valor> [contado|credito] [| nota]
     *
     * @return array{resumen: string, accion: array<string, mixed>}
     */
    public function prepararVenta(string $texto): array
    {
        [$principal, $nota] = $this->separar($texto);
        $palabras = $this->palabras($principal);

        $forma = Venta::FORMA_CONTADO;
        $ultima = F::normalizar((string) end($palabras));

        if (isset(self::FORMAS[$ultima])) {
            $forma = self::FORMAS[$ultima];
            array_pop($palabras);
        }

        [$monto, $clienteTexto] = $this->extraerMontoFinal($palabras, 'el valor de la venta');
        $cliente = $this->buscador->cliente($clienteTexto);
        $credito = $forma === Venta::FORMA_CREDITO;

        return [
            'resumen' => implode("\n", array_filter([
                '<b>🧾 Registrar venta</b>',
                'Cliente: <b>'.F::e($cliente->nombre).'</b>'.($cliente->local ? ' · '.F::e($cliente->local) : ''),
                'Valor: <b>'.F::dinero($monto).'</b>',
                'Forma de pago: '.($credito ? 'Crédito (vence el '.now()->addDays(30)->format('d/m/Y').', sin abono)' : 'Contado (queda pagada)'),
                $nota !== '' ? 'Nota: '.F::e($nota) : null,
                'Fecha: '.now()->format('d/m/Y'),
            ])),
            'accion' => [
                'tipo' => 'venta',
                'cliente_id' => $cliente->id,
                'monto' => $monto,
                'forma_pago' => $forma,
                'nota' => $nota !== '' ? mb_substr($nota, 0, 1000) : null,
            ],
        ];
    }

    /**
     * /abono <cliente> <valor> [método]
     * El abono se aplica a las ventas con saldo del cliente, de la más antigua a la más reciente.
     *
     * @return array{resumen: string, accion: array<string, mixed>}
     */
    public function prepararAbono(string $texto): array
    {
        [$principal] = $this->separar($texto);
        $palabras = $this->palabras($principal);

        $metodo = 'efectivo';
        $ultima = F::normalizar((string) end($palabras));

        if (array_key_exists($ultima, PagoVenta::METODOS)) {
            $metodo = $ultima;
            array_pop($palabras);
        }

        [$monto, $clienteTexto] = $this->extraerMontoFinal($palabras, 'el valor del abono');
        $cliente = $this->buscador->cliente($clienteTexto);
        $reparto = $this->repartirAbono($cliente, $monto);

        $lineas = [
            '<b>💰 Registrar abono</b>',
            'Cliente: <b>'.F::e($cliente->nombre).'</b>',
            'Valor: <b>'.F::dinero($monto).'</b> · '.PagoVenta::METODOS[$metodo],
            '',
            'Se aplica a:',
        ];

        foreach ($reparto as ['venta' => $venta, 'monto' => $parte]) {
            $lineas[] = "• <code>{$venta->numero}</code> ({$venta->fecha->format('d/m')}): "
                .F::dinero($parte).' de '.F::dinero($venta->saldo());
        }

        return [
            'resumen' => implode("\n", $lineas),
            'accion' => [
                'tipo' => 'abono',
                'cliente_id' => $cliente->id,
                'monto' => $monto,
                'metodo' => $metodo,
            ],
        ];
    }

    /**
     * Guarda una acción confirmada y devuelve el mensaje de resultado.
     *
     * @param  array<string, mixed>  $accion
     */
    public function ejecutar(array $accion): string
    {
        $usuario = User::where('email', config('services.telegram.user_email'))->first();
        $hoy = now()->toDateString();

        return match ($accion['tipo'] ?? null) {
            'gasto' => $this->ejecutarGasto($accion, $usuario, $hoy),
            'venta' => $this->ejecutarVenta($accion, $usuario, $hoy),
            'abono' => $this->ejecutarAbono($accion, $usuario),
            default => throw new RuntimeException('Acción desconocida.'),
        };
    }

    /**
     * @param  array<string, mixed>  $accion
     */
    private function ejecutarGasto(array $accion, ?User $usuario, string $hoy): string
    {
        $gasto = $this->gastos->crear([
            'fecha' => $hoy,
            'categoria_gasto_id' => $accion['categoria_gasto_id'],
            'descripcion' => $accion['descripcion'],
            'monto' => $accion['monto'],
            'metodo_pago' => null,
            'proveedor' => null,
            'user_id' => $usuario?->id,
        ]);

        return "✅ Gasto <code>{$gasto->numero}</code> registrado: ".F::e($gasto->descripcion)
            .' · '.F::e($gasto->categoria?->nombre).' · <b>'.F::dinero($gasto->monto).'</b>';
    }

    /**
     * @param  array<string, mixed>  $accion
     */
    private function ejecutarVenta(array $accion, ?User $usuario, string $hoy): string
    {
        $credito = $accion['forma_pago'] === Venta::FORMA_CREDITO;

        $venta = $this->ventas->crear([
            'cliente_id' => $accion['cliente_id'],
            'user_id' => $usuario?->id,
            'fecha' => $hoy,
            'total' => $accion['monto'],
            'forma_pago' => $accion['forma_pago'],
            'abono_inicial' => 0,
            'metodo_pago' => 'efectivo',
            'fecha_vencimiento' => $credito ? now()->addDays(30)->toDateString() : null,
            'observaciones' => $accion['nota'] ?? null,
        ]);

        return "✅ Venta <code>{$venta->numero}</code> registrada: ".F::e($venta->cliente?->nombre)
            .' · <b>'.F::dinero($venta->total).'</b> · '.$venta->formaPago()
            .($credito ? ' · saldo '.F::dinero($venta->saldo()) : '');
    }

    /**
     * @param  array<string, mixed>  $accion
     */
    private function ejecutarAbono(array $accion, ?User $usuario): string
    {
        $cliente = Cliente::findOrFail($accion['cliente_id']);

        return DB::transaction(function () use ($cliente, $accion, $usuario) {
            // Se recalcula el reparto: el saldo pudo cambiar desde que se preparó
            $reparto = $this->repartirAbono($cliente, (float) $accion['monto']);
            $aplicados = [];

            foreach ($reparto as ['venta' => $venta, 'monto' => $parte]) {
                $this->pagos->registrar($venta, [
                    'monto' => $parte,
                    'metodo' => $accion['metodo'],
                    'fecha' => now(),
                    'nota' => 'Abono vía Telegram',
                    'user_id' => $usuario?->id,
                ]);

                $aplicados[] = "<code>{$venta->numero}</code> ".F::dinero($parte);
            }

            $saldo = $this->consultas->ventasConSaldo($cliente)->sum(fn (Venta $v) => $v->saldo());

            return '✅ Abono de <b>'.F::dinero($accion['monto']).'</b> registrado a '.F::e($cliente->nombre)
                .' ('.implode(', ', $aplicados).').'
                ."\nSaldo pendiente del cliente: <b>".F::dinero($saldo).'</b>';
        });
    }

    /**
     * Reparte un abono entre las ventas con saldo del cliente (la más antigua primero).
     *
     * @return array<int, array{venta: Venta, monto: float}>
     */
    private function repartirAbono(Cliente $cliente, float $monto): array
    {
        $ventas = $this->consultas->ventasConSaldo($cliente);
        $saldoTotal = round($ventas->sum(fn (Venta $v) => $v->saldo()), 2);

        if ($saldoTotal <= 0) {
            throw new RuntimeException(F::e($cliente->nombre).' no tiene saldo pendiente.');
        }

        if ($monto > $saldoTotal + 0.001) {
            throw new RuntimeException('El abono ('.F::dinero($monto).') supera lo que debe '
                .F::e($cliente->nombre).' ('.F::dinero($saldoTotal).').');
        }

        $restante = $monto;
        $reparto = [];

        foreach ($ventas as $venta) {
            if ($restante <= 0.001) {
                break;
            }

            $parte = round(min($restante, $venta->saldo()), 2);
            $reparto[] = ['venta' => $venta, 'monto' => $parte];
            $restante = round($restante - $parte, 2);
        }

        return $reparto;
    }

    /**
     * Separa "texto principal | texto secundario".
     *
     * @return array{0: string, 1: string}
     */
    private function separar(string $texto): array
    {
        $partes = explode('|', $texto, 2);

        return [trim($partes[0]), trim($partes[1] ?? '')];
    }

    /**
     * @return array<int, string>
     */
    private function palabras(string $texto): array
    {
        return array_values(array_filter(preg_split('/\s+/u', trim($texto)) ?: [], fn ($p) => $p !== ''));
    }

    /**
     * Monto al inicio o al final del texto: "70000 comida" o "comida 70000".
     *
     * @return array{0: float, 1: string}
     */
    private function extraerMonto(string $texto, string $que): array
    {
        $palabras = $this->palabras($texto);

        if ($palabras !== [] && ($monto = F::monto($palabras[0])) !== null) {
            return [$monto, implode(' ', array_slice($palabras, 1))];
        }

        return $this->extraerMontoFinal($palabras, $que);
    }

    /**
     * Monto como última palabra; el resto es el nombre.
     *
     * @param  array<int, string>  $palabras
     * @return array{0: float, 1: string}
     */
    private function extraerMontoFinal(array $palabras, string $que): array
    {
        $ultima = array_pop($palabras);
        $monto = $ultima !== null ? F::monto($ultima) : null;

        if ($monto === null) {
            throw new RuntimeException("No entendí {$que}. Escríbelo en números, por ejemplo 70000, 70.000 o 70k.");
        }

        return [$monto, implode(' ', $palabras)];
    }
}

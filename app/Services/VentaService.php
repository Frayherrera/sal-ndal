<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\MovimientoInventario;
use App\Models\Venta;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class VentaService
{
    public function __construct(
        protected MovimientoInventarioService $movimientoService,
        protected PagoVentaService $pagoService,
    ) {}

    /**
     * Registra una venta por valor total (sin productos ni movimientos de
     * inventario) y el abono inicial si lo hay. Todo en una sola transacción.
     *
     * @param  array<string, mixed>  $data
     */
    public function crear(array $data): Venta
    {
        $cliente = Cliente::find($data['cliente_id'] ?? null);

        if (! $cliente) {
            throw new RuntimeException('Debes seleccionar un cliente.');
        }

        $total = round((float) ($data['total'] ?? 0), 2);

        if ($total <= 0) {
            throw new RuntimeException('El valor total de la venta debe ser mayor a cero.');
        }

        $fecha = isset($data['fecha']) ? Carbon::parse($data['fecha']) : now();

        $formaPago = $data['forma_pago'] ?? Venta::FORMA_CREDITO;

        $abono = round((float) ($data['abono_inicial'] ?? 0), 2);

        if ($abono < 0) {
            throw new RuntimeException('El abono inicial no puede ser negativo.');
        }

        // Modo contado: el abono cubre el total automáticamente
        if ($formaPago === Venta::FORMA_CONTADO) {
            $abono = $total;
        }

        if ($abono > $total + 0.001) {
            throw new RuntimeException('El abono inicial no puede superar el total de la venta.');
        }

        $saldo = round($total - $abono, 2);

        // Modo contado: no hay vencimiento; modo crédito: vencimiento si hay saldo
        if ($formaPago === Venta::FORMA_CONTADO) {
            $fechaVencimiento = null;
        } else {
            if ($saldo > 0 && empty($data['fecha_vencimiento'])) {
                throw new RuntimeException('Indica la fecha de vencimiento del saldo pendiente.');
            }
            $fechaVencimiento = $saldo > 0
                ? Carbon::parse($data['fecha_vencimiento'])->toDateString()
                : null;
        }

        return DB::transaction(function () use ($cliente, $fecha, $total, $abono, $fechaVencimiento, $formaPago, $data) {
            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'fecha' => $fecha,
                'subtotal' => $total,
                'descuento_total' => 0,
                'total' => $total,
                'estado' => Venta::ESTADO_CONFIRMADA,
                'forma_pago' => $formaPago,
                'fecha_vencimiento' => $fechaVencimiento,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            $venta->numero = 'VTA-'.str_pad((string) $venta->id, 5, '0', STR_PAD_LEFT);
            $venta->save();

            if ($abono > 0) {
                $this->pagoService->registrar($venta, [
                    'monto' => $abono,
                    'metodo' => $data['metodo_pago'] ?? 'efectivo',
                    'referencia' => $data['referencia_pago'] ?? null,
                    'fecha' => $venta->fecha,
                    'nota' => 'Abono inicial',
                    'user_id' => $venta->user_id,
                ]);
            }

            return $venta->fresh(['cliente', 'pagos']);
        });
    }

    /**
     * Anula una venta. No se permite si ya tiene abonos registrados.
     * Las ventas antiguas (registradas por productos) además revierten sus
     * movimientos de inventario; las ventas por valor total no tienen.
     */
    public function anular(Venta $venta, string $motivo): Venta
    {
        if ($venta->estado === Venta::ESTADO_ANULADA) {
            throw new RuntimeException('La venta ya está anulada.');
        }

        if ($venta->pagos()->exists()) {
            throw new RuntimeException('No se puede anular una venta con abonos registrados. Gestiona primero la devolución del dinero.');
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException('Debes indicar el motivo de la anulación.');
        }

        return DB::transaction(function () use ($venta, $motivo) {
            $movimientos = MovimientoInventario::where('venta_id', $venta->id)
                ->whereNull('movimiento_original_id')
                ->get();

            foreach ($movimientos as $movimiento) {
                $this->movimientoService->anular($movimiento, $motivo);
            }

            $venta->update([
                'estado' => Venta::ESTADO_ANULADA,
                'anulada_at' => now(),
                'anulada_por' => auth()->id(),
                'motivo_anulacion' => $motivo,
            ]);

            return $venta->fresh();
        });
    }
}

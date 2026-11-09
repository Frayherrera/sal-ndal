<?php

namespace App\Services;

use App\Models\PagoVenta;
use App\Models\Venta;
use RuntimeException;

class PagoVentaService
{
    /**
     * Registra un abono sobre una venta, validando que no supere el saldo pendiente.
     *
     * @param  array<string, mixed>  $data
     */
    public function registrar(Venta $venta, array $data): PagoVenta
    {
        if ($venta->estado === Venta::ESTADO_ANULADA) {
            throw new RuntimeException('No se pueden registrar abonos en una venta anulada.');
        }

        $monto = round((float) ($data['monto'] ?? 0), 2);

        if ($monto <= 0) {
            throw new RuntimeException('El monto del abono debe ser mayor a cero.');
        }

        $pagado = round((float) $venta->pagos()->sum('monto'), 2);
        $saldo = round((float) $venta->total - $pagado, 2);

        if ($saldo <= 0) {
            throw new RuntimeException('La venta ya está pagada en su totalidad.');
        }

        if ($monto > $saldo + 0.001) {
            throw new RuntimeException(
                'El abono ($'.number_format($monto, 2, ',', '.').') supera el saldo pendiente ($'
                .number_format($saldo, 2, ',', '.').').'
            );
        }

        $metodo = $data['metodo'] ?? 'efectivo';

        if (! array_key_exists($metodo, PagoVenta::METODOS)) {
            throw new RuntimeException('Método de pago no válido.');
        }

        return $venta->pagos()->create([
            'fecha' => $data['fecha'] ?? now(),
            'monto' => $monto,
            'metodo' => $metodo,
            'referencia' => $data['referencia'] ?? null,
            'nota' => $data['nota'] ?? null,
            'user_id' => $data['user_id'] ?? auth()->id(),
        ]);
    }
}

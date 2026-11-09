<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\ConfiguracionInventario;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
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
     * Registra una venta: descuenta stock, guarda snapshots de precio y
     * registra el abono inicial si lo hay. Todo en una sola transacción.
     *
     * @param  array<string, mixed>  $data
     */
    public function crear(array $data): Venta
    {
        $cliente = Cliente::find($data['cliente_id'] ?? null);

        if (! $cliente) {
            throw new RuntimeException('Debes seleccionar un cliente.');
        }

        $lineas = $this->prepararLineas($data['lineas'] ?? []);

        if ($lineas === []) {
            throw new RuntimeException('La venta debe tener al menos un producto.');
        }

        $fecha = isset($data['fecha']) ? Carbon::parse($data['fecha']) : now();

        $subtotal = round(array_sum(array_column($lineas, 'subtotal')), 2);
        $descuentoTotal = round(array_sum(array_column($lineas, 'descuento_monto')), 2);
        $total = round($subtotal - $descuentoTotal, 2);

        $formaPago = $data['forma_pago'] ?? 'credito';

        $abono = round((float) ($data['abono_inicial'] ?? 0), 2);

        if ($abono < 0) {
            throw new RuntimeException('El abono inicial no puede ser negativo.');
        }

        // Modo contado: el abono cubre el total automáticamente
        if ($formaPago === 'contado') {
            $abono = $total;
        }

        if ($abono > $total + 0.001) {
            throw new RuntimeException('El abono inicial no puede superar el total de la venta.');
        }

        $saldo = round($total - $abono, 2);

        // Modo contado: no hay vencimiento; modo crédito: vencimiento si hay saldo
        if ($formaPago === 'contado') {
            $fechaVencimiento = null;
        } else {
            if ($saldo > 0 && empty($data['fecha_vencimiento'])) {
                throw new RuntimeException('Indica la fecha de vencimiento del saldo pendiente.');
            }
            $fechaVencimiento = $saldo > 0
                ? Carbon::parse($data['fecha_vencimiento'])->toDateString()
                : null;
        }

        $this->validarStock($lineas);

        return DB::transaction(function () use ($cliente, $lineas, $fecha, $subtotal, $descuentoTotal, $total, $abono, $saldo, $fechaVencimiento, $formaPago, $data) {
            $venta = Venta::create([
                'cliente_id' => $cliente->id,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'fecha' => $fecha,
                'subtotal' => $subtotal,
                'descuento_total' => $descuentoTotal,
                'total' => $total,
                'estado' => Venta::ESTADO_CONFIRMADA,
                'forma_pago' => $formaPago,
                'fecha_vencimiento' => $fechaVencimiento,
                'observaciones' => $data['observaciones'] ?? null,
            ]);

            $venta->numero = 'VTA-'.str_pad((string) $venta->id, 5, '0', STR_PAD_LEFT);
            $venta->save();

            foreach ($lineas as $linea) {
                $detalle = $venta->detalles()->create([
                    'producto_terminado_id' => $linea['producto']->id,
                    'cantidad_unidades' => $linea['cantidad_unidades'],
                    'precio_unidad' => $linea['precio_unidad'],
                    'descuento_tipo' => $linea['descuento_tipo'],
                    'descuento_valor' => $linea['descuento_valor'],
                    'descuento_monto' => $linea['descuento_monto'],
                    'subtotal' => $linea['subtotal'],
                    'total' => $linea['total'],
                ]);

                $this->movimientoService->registrar([
                    'tipo' => 'venta_despacho',
                    'origen' => $linea['producto'],
                    'cantidad' => $detalle->unidades(),
                    'documento' => $venta->numero,
                    'referencia' => 'Venta a '.$cliente->nombre,
                    'fecha' => $venta->fecha,
                    'user_id' => $venta->user_id,
                    'venta_id' => $venta->id,
                ]);
            }

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

            return $venta->fresh(['cliente', 'detalles.producto', 'pagos']);
        });
    }

    /**
     * Anula una venta revirtiendo todos sus movimientos de inventario.
     * No se permite si ya tiene abonos registrados.
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

    /**
     * Calcula el descuento de una línea, con tope en el valor bruto.
     */
    public function calcularDescuento(float $bruto, ?string $tipo, float $valor): float
    {
        $tipo = $tipo ?: 'monto';
        $valor = max($valor, 0);

        if ($tipo === 'porcentaje') {
            if ($valor > 100) {
                throw new RuntimeException('El descuento porcentual no puede superar 100%.');
            }

            return round($bruto * $valor / 100, 2);
        }

        return round(min($valor, $bruto), 2);
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineas
     * @return array<int, array<string, mixed>>
     */
    private function prepararLineas(array $lineas): array
    {
        $raw = [];

        foreach ($lineas as $linea) {
            $productoId = (int) ($linea['producto_terminado_id'] ?? 0);
            $cantidad = (int) ($linea['cantidad_unidades'] ?? 0);

            if ($productoId <= 0) {
                throw new RuntimeException('Selecciona un producto en todas las líneas de la venta.');
            }

            if ($cantidad <= 0) {
                throw new RuntimeException('Cada línea debe tener al menos una unidad.');
            }

            $raw[] = [
                'producto_id' => $productoId,
                'cantidad_unidades' => $cantidad,
                'descuento_tipo' => $linea['descuento_tipo'] ?? 'monto',
                'descuento_valor' => (float) ($linea['descuento_valor'] ?? 0),
            ];
        }

        if ($raw === []) {
            return [];
        }

        $productos = ProductoTerminado::with('inventario')
            ->whereIn('id', array_unique(array_column($raw, 'producto_id')))
            ->get()
            ->keyBy('id');

        $preparadas = [];

        foreach ($raw as $linea) {
            $producto = $productos->get($linea['producto_id']);

            if (! $producto) {
                throw new RuntimeException('Uno de los productos seleccionados no existe.');
            }

            if (! $producto->activo) {
                throw new RuntimeException("El producto \"{$producto->nombre}\" está inactivo y no se puede vender.");
            }

            $precioUnidad = round((float) $producto->precio_unitario(), 2);
            $bruto = round($precioUnidad * $linea['cantidad_unidades'], 2);
            $descuento = $this->calcularDescuento(
                $bruto,
                $linea['descuento_tipo'],
                $linea['descuento_valor']
            );

            $preparadas[] = [
                'producto' => $producto,
                'cantidad_unidades' => $linea['cantidad_unidades'],
                'precio_unidad' => $precioUnidad,
                'descuento_tipo' => $linea['descuento_tipo'] ?: 'monto',
                'descuento_valor' => $linea['descuento_valor'],
                'descuento_monto' => $descuento,
                'subtotal' => $bruto,
                'total' => round($bruto - $descuento, 2),
            ];
        }

        return $preparadas;
    }

    /**
     * Verifica stock de todos los productos antes de crear la venta.
     *
     * @param  array<int, array<string, mixed>>  $lineas
     */
    private function validarStock(array $lineas): void
    {
        if (ConfiguracionInventario::permiteStockNegativo()) {
            return;
        }

        $requeridos = [];

        foreach ($lineas as $linea) {
            $id = $linea['producto']->id;

            if (! isset($requeridos[$id])) {
                $requeridos[$id] = ['producto' => $linea['producto'], 'unidades' => 0];
            }

            $requeridos[$id]['unidades'] += $linea['cantidad_unidades'];
        }

        foreach ($requeridos as $requerido) {
            $producto = $requerido['producto'];
            $unidades = $requerido['unidades'];
            $disponible = $producto->stock_disponible();

            if ($disponible < $unidades) {
                throw new RuntimeException(
                    "Stock insuficiente de \"{$producto->nombre}\": disponible {$disponible} u, "
                    ."se requieren {$unidades} u."
                );
            }
        }
    }
}

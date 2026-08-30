<?php

namespace App\Services;

use App\Models\ConfiguracionInventario;
use App\Models\InventarioMateriaPrima;
use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MovimientoInventarioService
{
    /**
     * Registra un movimiento de inventario y actualiza el stock.
     *
     * Tipos: compra_recepcion | consumo_produccion | producto_producido |
     *       venta_despacho | devolucion | ajuste_positivo | ajuste_negativo
     *
     * @param  array<string, mixed>  $data
     */
    public function registrar(array $data): MovimientoInventario
    {
        $tipo = $data['tipo'];
        $origen = $data['origen']; // instancia de Model (MateriaPrima|ProductoTerminado)
        $cantidad = (int) $data['cantidad'];

        if ($cantidad <= 0) {
            throw new RuntimeException('La cantidad debe ser mayor a cero.');
        }

        $direccion = $this->direccionPara($tipo);

        return DB::transaction(function () use ($data, $origen, $cantidad, $tipo, $direccion) {
            $nuevoSaldo = $this->aplicarStock($tipo, $origen, $cantidad);

            return MovimientoInventario::create([
                'tipo' => $tipo,
                'origen_type' => $origen->getMorphClass(),
                'origen_id' => $origen->getKey(),
                'cantidad' => $cantidad,
                'direccion' => $direccion,
                'saldo' => $nuevoSaldo,
                'documento' => $data['documento'] ?? null,
                'referencia' => $data['referencia'] ?? null,
                'motivo' => $data['motivo'] ?? null,
                'costo_unitario' => $data['costo_unitario'] ?? null,
                'costo_total' => $data['costo_total'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
                'conteo_fisico_id' => $data['conteo_fisico_id'] ?? null,
                'fecha' => $data['fecha'] ?? now(),
            ]);
        });
    }

    public function direccionPara(string $tipo): string
    {
        return in_array($tipo, [
            'compra_recepcion',
            'producto_producido',
            'devolucion',
            'ajuste_positivo',
        ], true) ? 'entrada' : 'salida';
    }

    /**
     * Actualiza el stock del origen y devuelve el nuevo saldo resultante.
     */
    private function aplicarStock(string $tipo, mixed $origen, int $cantidad): int
    {
        $direccion = $this->direccionPara($tipo);

        if ($origen instanceof MateriaPrima) {
            return $this->aplicarStockMateriaPrima($origen, $cantidad, $direccion);
        }

        if ($origen instanceof ProductoTerminado) {
            return $this->aplicarStockProducto($origen, $cantidad, $direccion);
        }

        throw new RuntimeException('Origen de inventario no válido para este tipo de movimiento.');
    }

    private function aplicarStockMateriaPrima(MateriaPrima $mp, int $gramos, string $direccion): int
    {
        $inv = InventarioMateriaPrima::firstOrCreate(
            ['materia_prima_id' => $mp->getKey()],
            ['stock_gramos' => 0, 'costo_promedio' => 0]
        );

        if ($direccion === 'entrada') {
            $inv->stock_gramos += $gramos;
        } else {
            $this->validarSaldo($inv->stock_gramos, $gramos, $mp->nombre);
            $inv->stock_gramos -= $gramos;
        }

        $inv->save();

        return $inv->stock_gramos;
    }

    private function aplicarStockProducto(ProductoTerminado $pt, int $unidades, string $direccion): int
    {
        $inv = InventarioProductoTerminado::firstOrCreate(
            ['producto_terminado_id' => $pt->getKey()],
            ['disponible' => 0, 'comprometido' => 0]
        );

        if ($direccion === 'entrada') {
            $inv->disponible += $unidades;
            $saldo = $inv->disponible;
        } else {
            $this->validarSaldo($inv->disponible, $unidades, $pt->nombre);
            $inv->disponible -= $unidades;
            $saldo = $inv->disponible;
        }

        $inv->save();

        return $saldo;
    }

    private function validarSaldo(int $disponible, int $cantidad, string $nombre): void
    {
        if (! ConfiguracionInventario::permiteStockNegativo() && $disponible < $cantidad) {
            throw new RuntimeException(
                "Stock insuficiente de \"{$nombre}\": disponible {$disponible}, se requieren {$cantidad}."
            );
        }
    }

    /**
     * Registra una devolución que puede sumar o restar stock.
     */
    public function registrarDevolucion(mixed $origen, int $cantidad, string $direccion, ?string $motivo = null, ?string $documento = null): MovimientoInventario
    {
        $esMateriaPrima = $origen instanceof MateriaPrima;

        return DB::transaction(function () use ($origen, $cantidad, $direccion, $motivo, $documento, $esMateriaPrima) {
            if ($direccion === 'entrada') {
                $nuevoSaldo = $esMateriaPrima
                    ? $this->aplicarStockMateriaPrima($origen, $cantidad, 'entrada')
                    : $this->aplicarStockProducto($origen, $cantidad, 'entrada');
            } else {
                $nuevoSaldo = $esMateriaPrima
                    ? $this->aplicarStockMateriaPrima($origen, $cantidad, 'salida')
                    : $this->aplicarStockProducto($origen, $cantidad, 'salida');
            }

            return MovimientoInventario::create([
                'tipo' => 'devolucion',
                'origen_type' => $origen->getMorphClass(),
                'origen_id' => $origen->getKey(),
                'cantidad' => $cantidad,
                'direccion' => $direccion,
                'saldo' => $nuevoSaldo,
                'documento' => $documento,
                'referencia' => null,
                'motivo' => $motivo,
                'user_id' => auth()->id(),
                'fecha' => now(),
            ]);
        });
    }

    /**
     * Produce $unidades de un producto terminado según su receta (BOM).
     *
     * En una sola transacción:
     *  - registra un movimiento "consumo_produccion" (salida) por cada materia
     *    prima de la receta (resta materia prima),
     *  - registra un movimiento "producto_producido" (entrada) del producto.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, MovimientoInventario>
     */
    public function producir(ProductoTerminado $producto, int $unidades, array $data = []): array
    {
        if ($unidades <= 0) {
            throw new RuntimeException('La cantidad a producir debe ser mayor a cero.');
        }

        $receta = $producto->receta()->with('materiaPrima')->get();

        if ($receta->isEmpty()) {
            throw new RuntimeException("El producto \"{$producto->nombre}\" no tiene receta definida. Define su receta antes de producir.");
        }

        $documento = $data['documento'] ?? null;
        $fecha = $data['fecha'] ?? now();
        $user_id = $data['user_id'] ?? auth()->id();

        // Pre-validación de stock de todas las materias primas antes de aplicar.
        $requerimientos = $receta->map(fn ($linea) => [
            'materia' => $linea->materiaPrima,
            'gramos' => (int) round((float) $linea->gramos_por_unidad * $unidades),
        ]);

        if (! ConfiguracionInventario::permiteStockNegativo()) {
            foreach ($requerimientos as $req) {
                $mp = $req['materia'];
                if ($mp && $mp->stock_gramos() < $req['gramos']) {
                    throw new RuntimeException(
                        "Stock insuficiente de \"{$mp->nombre}\": disponible {$mp->stock_gramos()} g, "
                        ."se requieren {$req['gramos']} g para producir {$unidades} unidad(es)."
                    );
                }
            }
        }

        return DB::transaction(function () use ($producto, $unidades, $requerimientos, $documento, $fecha, $user_id) {
            $movimientos = [];

            foreach ($requerimientos as $req) {
                $mp = $req['materia'];
                if (! $mp) {
                    continue;
                }
                $movimientos[] = $this->registrar([
                    'tipo' => 'consumo_produccion',
                    'origen' => $mp,
                    'cantidad' => $req['gramos'],
                    'documento' => $documento,
                    'referencia' => "Producción de {$producto->nombre}",
                    'fecha' => $fecha,
                    'user_id' => $user_id,
                ]);
            }

            $movimientos[] = $this->registrar([
                'tipo' => 'producto_producido',
                'origen' => $producto,
                'cantidad' => $unidades,
                'documento' => $documento,
                'referencia' => 'Producción por receta',
                'fecha' => $fecha,
                'user_id' => $user_id,
            ]);

            return $movimientos;
        });
    }

    /**
     * Anula un movimiento creando la reversión espejo. No borra el original.
     */
    public function anular(MovimientoInventario $movimiento, ?string $motivo = null): MovimientoInventario
    {
        if ($movimiento->tipo === 'anulacion_reversion') {
            throw new RuntimeException('No se puede anular una anulación.');
        }

        if ($movimiento->movimiento_original_id) {
            throw new RuntimeException('Este movimiento es una reversión y no puede anularse.');
        }

        $direccionRevertida = $movimiento->direccion === 'entrada' ? 'salida' : 'entrada';
        $origen = $movimiento->origen;
        $cantidad = $movimiento->cantidad;

        return DB::transaction(function () use ($movimiento, $origen, $cantidad, $direccionRevertida, $motivo) {
            $nuevoSaldo = $origen instanceof MateriaPrima
                ? $this->aplicarStockMateriaPrima($origen, $cantidad, $direccionRevertida)
                : $this->aplicarStockProducto($origen, $cantidad, $direccionRevertida);

            return MovimientoInventario::create([
                'tipo' => 'anulacion_reversion',
                'origen_type' => $origen->getMorphClass(),
                'origen_id' => $origen->getKey(),
                'cantidad' => $cantidad,
                'direccion' => $direccionRevertida,
                'saldo' => $nuevoSaldo,
                'documento' => $movimiento->documento,
                'referencia' => 'Anula movimiento #'.$movimiento->id,
                'motivo' => $motivo,
                'user_id' => auth()->id(),
                'movimiento_original_id' => $movimiento->id,
                'fecha' => now(),
            ]);
        });
    }
}

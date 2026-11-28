<?php

namespace App\Services;

use App\Models\InventarioProductoTerminado;
use App\Models\MovimientoInventario;
use App\Models\Producto;
use App\Models\ProductoTerminado;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Productos y sus presentaciones. Cada presentación (ProductoTerminado) es un
 * SKU con stock propio; el Producto solo las agrupa bajo un nombre.
 */
class ProductoTerminadoService
{
    public function generarCodigo(): string
    {
        $prefijo = 'PT';
        $base = $prefijo.'-'.strtoupper(Str::random(4));
        while (ProductoTerminado::where('codigo', $base)->exists()) {
            $base = $prefijo.'-'.strtoupper(Str::random(4));
        }

        return $base;
    }

    /** Crea una presentación suelta (con su producto por nombre) y su inventario en cero. */
    public function crear(array $data): ProductoTerminado
    {
        $data['codigo'] = filled($data['codigo'] ?? null) ? $data['codigo'] : $this->generarCodigo();
        $data['activo'] = $data['activo'] ?? true;

        return DB::transaction(function () use ($data) {
            $pt = ProductoTerminado::create($data);
            InventarioProductoTerminado::create([
                'producto_terminado_id' => $pt->id,
                'disponible' => 0,
                'comprometido' => 0,
            ]);

            return $pt;
        });
    }

    /**
     * Crea o actualiza un producto con sus presentaciones en una transacción.
     *
     * Cada presentación: [id?, categoria (B2C|ESP), codigo?, presentacion?, peso_neto,
     * precio_venta, stock_minimo, activo]. Las presentaciones existentes que no
     * vienen se eliminan si nunca se usaron; si tienen historial se desactivan.
     *
     * @param  array<int, array<string, mixed>>  $presentaciones
     */
    public function guardarProducto(?Producto $producto, string $nombre, array $presentaciones, bool $activo = true): Producto
    {
        $nombre = trim($nombre);
        $this->validarPresentaciones($presentaciones);

        return DB::transaction(function () use ($producto, $nombre, $presentaciones, $activo) {
            $producto ??= new Producto;
            $producto->fill(['nombre' => $nombre, 'activo' => $activo])->save();

            $conservar = [];
            foreach ($presentaciones as $datos) {
                $campos = [
                    'nombre' => $nombre,
                    'categoria' => $datos['categoria'],
                    'presentacion' => $datos['presentacion'] ?? 'bolsa',
                    'peso_neto' => (float) ($datos['peso_neto'] ?? 0),
                    'precio_venta' => (float) ($datos['precio_venta'] ?? 0),
                    'stock_minimo' => (float) ($datos['stock_minimo'] ?? 0),
                    'activo' => (bool) ($datos['activo'] ?? true),
                ];

                $existente = filled($datos['id'] ?? null)
                    ? $producto->presentaciones()->whereKey($datos['id'])->first()
                    : null;

                if ($existente) {
                    if (filled($datos['codigo'] ?? null)) {
                        $campos['codigo'] = $datos['codigo'];
                    }
                    $existente->update($campos);
                    $conservar[] = $existente->id;
                } else {
                    $nueva = $this->crear($campos + [
                        'producto_id' => $producto->id,
                        'codigo' => $datos['codigo'] ?? null,
                    ]);
                    $conservar[] = $nueva->id;
                }
            }

            // Las que se quitaron del formulario
            $producto->presentaciones()->whereNotIn('id', $conservar)->get()
                ->each(fn (ProductoTerminado $pt) => $this->puedeEliminarse($pt)
                    ? $this->borrar($pt)
                    : $pt->update(['activo' => false]));

            return $producto->load('presentaciones.inventario');
        });
    }

    /** Una presentación nunca usada (sin kardex, camión, ventas ni conteos) se puede borrar. */
    /**
     * Fija el stock de bodega de una presentación a mano. Registra en el kardex un
     * ajuste_positivo / ajuste_negativo por la diferencia (documento AJUSTE-MANUAL).
     * Devuelve el movimiento, o null si el stock ya era ese.
     */
    public function ajustarStock(ProductoTerminado $pt, int $nuevoStock, string $motivo): ?MovimientoInventario
    {
        if ($nuevoStock < 0) {
            throw ValidationException::withMessages(['paquetes' => 'El stock no puede ser negativo.']);
        }

        $diferencia = $nuevoStock - $pt->stock_disponible();
        if ($diferencia === 0) {
            return null;
        }

        return app(MovimientoInventarioService::class)->registrar([
            'tipo' => $diferencia > 0 ? 'ajuste_positivo' : 'ajuste_negativo',
            'origen' => $pt,
            'cantidad' => abs($diferencia),
            'documento' => 'AJUSTE-MANUAL',
            'motivo' => $motivo,
        ]);
    }

    public function puedeEliminarse(ProductoTerminado $pt): bool
    {
        return ! $pt->movimientos()->exists()
            && ! DB::table('detalle_viaje_camion')->where('producto_terminado_id', $pt->id)->exists()
            && ! DB::table('detalle_venta')->where('producto_terminado_id', $pt->id)->exists()
            && ! DB::table('detalle_conteo_fisico')->where('producto_terminado_id', $pt->id)->exists();
    }

    /**
     * Lo que se borraría al eliminar el producto, para la pantalla de confirmación.
     *
     * @return array{movimientos: int, viajes: int, ventas: int, conteos: int, stock: int, en_ruta: ?string, historial: bool}
     */
    public function resumenEliminacion(Producto $producto): array
    {
        $ids = $producto->presentaciones()->pluck('id');

        $resumen = [
            'movimientos' => $this->movimientosDe($ids)->count(),
            'viajes' => DB::table('detalle_viaje_camion')->whereIn('producto_terminado_id', $ids)->distinct()->count('viaje_camion_id'),
            'ventas' => DB::table('detalle_venta')->whereIn('producto_terminado_id', $ids)->distinct()->count('venta_id'),
            'conteos' => DB::table('detalle_conteo_fisico')->whereIn('producto_terminado_id', $ids)->count(),
            'stock' => (int) DB::table('inventario_producto_terminado')->whereIn('producto_terminado_id', $ids)->sum('disponible'),
            'en_ruta' => $this->viajeEnRuta($ids),
        ];
        $resumen['historial'] = $resumen['movimientos'] + $resumen['viajes'] + $resumen['ventas'] + $resumen['conteos'] > 0;

        return $resumen;
    }

    /**
     * Borra el producto con todas sus presentaciones y su historial: kardex, receta,
     * inventario, líneas de viajes del camión, de ventas antiguas y de conteos físicos.
     * No se puede deshacer. Se bloquea si alguna presentación va en el camión en ruta.
     */
    public function eliminarProducto(Producto $producto): void
    {
        $presentaciones = $producto->presentaciones()->get();
        $ids = $presentaciones->pluck('id');

        if ($viaje = $this->viajeEnRuta($ids)) {
            throw new RuntimeException("Este producto va cargado en el viaje {$viaje}, que está en ruta. Cierra o anula el viaje antes de eliminarlo.");
        }

        DB::transaction(function () use ($producto, $presentaciones, $ids) {
            $movimientos = $this->movimientosDe($ids)->pluck('id');
            // Las reversiones apuntan a su movimiento original: se sueltan antes de borrar
            MovimientoInventario::whereIn('movimiento_original_id', $movimientos)->update(['movimiento_original_id' => null]);
            MovimientoInventario::whereIn('id', $movimientos)->delete();

            DB::table('detalle_conteo_fisico')->whereIn('producto_terminado_id', $ids)->delete();
            DB::table('detalle_viaje_camion')->whereIn('producto_terminado_id', $ids)->delete();
            DB::table('detalle_venta')->whereIn('producto_terminado_id', $ids)->delete();

            $presentaciones->each(fn ($pt) => $this->borrar($pt));
            $producto->delete();
        });
    }

    private function movimientosDe(Collection $ids): Builder
    {
        return MovimientoInventario::where('origen_type', (new ProductoTerminado)->getMorphClass())
            ->whereIn('origen_id', $ids);
    }

    /** Número del viaje en ruta que lleva alguna de estas presentaciones, o null. */
    private function viajeEnRuta(Collection $ids): ?string
    {
        return DB::table('detalle_viaje_camion')
            ->join('viajes_camion', 'viajes_camion.id', '=', 'detalle_viaje_camion.viaje_camion_id')
            ->whereIn('detalle_viaje_camion.producto_terminado_id', $ids)
            ->where('viajes_camion.estado', 'en_ruta')
            ->value('viajes_camion.numero');
    }

    /** Activa o desactiva el producto y todas sus presentaciones. */
    public function toggleProducto(Producto $producto): Producto
    {
        $activo = ! $producto->activo;
        DB::transaction(function () use ($producto, $activo) {
            $producto->update(['activo' => $activo]);
            $producto->presentaciones()->update(['activo' => $activo]);
        });

        return $producto;
    }

    public function eliminar(ProductoTerminado $pt): void
    {
        if (! $this->puedeEliminarse($pt)) {
            throw new RuntimeException('No se puede eliminar un producto terminado con movimientos registrados. Desactívelo en su lugar.');
        }

        $this->borrar($pt);
    }

    public function actualizar(ProductoTerminado $pt, array $data): ProductoTerminado
    {
        $pt->update($data);

        return $pt;
    }

    public function toggleActivo(ProductoTerminado $pt): ProductoTerminado
    {
        $pt->update(['activo' => ! $pt->activo]);

        return $pt;
    }

    private function borrar(ProductoTerminado $pt): void
    {
        $pt->receta()->delete();
        $pt->inventario()->delete();
        $pt->delete();
    }

    /** Al menos una presentación, línea válida y sin repetir línea + peso. */
    private function validarPresentaciones(array $presentaciones): void
    {
        if ($presentaciones === []) {
            throw ValidationException::withMessages(['presentaciones' => 'Agrega al menos una presentación.']);
        }

        $vistas = [];
        foreach ($presentaciones as $i => $p) {
            if (! array_key_exists($p['categoria'] ?? '', ProductoTerminado::LINEAS)) {
                throw ValidationException::withMessages(["presentaciones.$i.categoria" => 'La línea debe ser B2C o Especial.']);
            }

            $clave = $p['categoria'].'|'.round((float) ($p['peso_neto'] ?? 0), 2);
            if (isset($vistas[$clave])) {
                $linea = ProductoTerminado::LINEAS[$p['categoria']];
                throw ValidationException::withMessages([
                    "presentaciones.$i.peso_neto" => "Hay dos presentaciones {$linea} con el mismo peso.",
                ]);
            }
            $vistas[$clave] = true;
        }
    }
}

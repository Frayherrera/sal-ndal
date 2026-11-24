<?php

namespace App\Services;

use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use Illuminate\Support\Collection;

/**
 * Datos de los reportes PDF del inventario actual (materia prima y producto terminado).
 * Incluye los artículos activos y los inactivos que todavía tienen stock.
 *
 * Estado de cada artículo: `agotado` (sin stock), `bajo` (tiene mínimo y está en o por
 * debajo de él) u `ok`.
 */
class ReporteInventarioService
{
    public const ESTADOS = ['agotado' => 'Agotado', 'bajo' => 'Bajo mínimo', 'ok' => 'OK'];

    public function __construct(private ViajeCamionService $camion) {}

    /**
     * @return array<string, mixed>
     */
    public function materiaPrima(): array
    {
        $filas = MateriaPrima::with('inventario')->orderBy('nombre')->get()
            ->filter(fn (MateriaPrima $mp) => $mp->activo || $mp->stock_gramos() > 0)
            ->map(function (MateriaPrima $mp) {
                $kg = round($mp->stock_kg(), 3);
                $minimo = round((float) $mp->stock_minimo, 3);

                return [
                    'codigo' => $mp->codigo,
                    'nombre' => $mp->nombre,
                    'categoria' => filled($mp->categoria) ? $mp->categoria : 'Sin categoría',
                    'proveedor' => $mp->proveedor,
                    'ubicacion' => $mp->ubicacion,
                    'molido' => (bool) $mp->es_molido,
                    'activo' => (bool) $mp->activo,
                    'stock' => $kg,
                    'minimo' => $minimo,
                    'estado' => $this->estado($kg, $minimo),
                ];
            })
            ->values();

        return [
            'corte' => now(),
            'kpis' => $this->kpis($filas) + ['total_kg' => round($filas->sum('stock'), 3)],
            'grupos' => $this->agrupar($filas, fn (Collection $g) => ['stock' => round($g->sum('stock'), 3)]),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function productoTerminado(): array
    {
        $enCamion = $this->camion->stockCamion();

        $filas = ProductoTerminado::with('inventario')->orderBy('nombre')->get()
            ->filter(fn (ProductoTerminado $p) => $p->activo || $p->stock_disponible() > 0 || ($enCamion[$p->id] ?? 0) > 0)
            ->map(function (ProductoTerminado $p) use ($enCamion) {
                $bodega = $p->stock_disponible();
                $camion = (int) ($enCamion[$p->id] ?? 0);
                $minimo = (int) round((float) $p->stock_minimo);

                return [
                    'codigo' => $p->codigo,
                    'nombre' => $p->nombre,
                    'categoria' => filled($p->categoria) ? $p->categoria : 'Sin categoría',
                    'presentacion' => $p->presentacion,
                    'activo' => (bool) $p->activo,
                    'bodega' => $bodega,
                    'camion' => $camion,
                    'total' => $bodega + $camion,
                    'minimo' => $minimo,
                    // El estado mira la bodega: es lo que hay disponible para surtir
                    'estado' => $this->estado($bodega, $minimo),
                ];
            })
            ->values();

        return [
            'corte' => now(),
            'kpis' => $this->kpis($filas) + [
                'bodega' => (int) $filas->sum('bodega'),
                'camion' => (int) $filas->sum('camion'),
                'total' => (int) $filas->sum('total'),
            ],
            'grupos' => $this->agrupar($filas, fn (Collection $g) => [
                'bodega' => (int) $g->sum('bodega'),
                'camion' => (int) $g->sum('camion'),
                'total' => (int) $g->sum('total'),
            ]),
        ];
    }

    private function estado(float|int $stock, float|int $minimo): string
    {
        if ($stock <= 0) {
            return 'agotado';
        }

        return $minimo > 0 && $stock <= $minimo ? 'bajo' : 'ok';
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $filas
     * @return array<string, int>
     */
    private function kpis(Collection $filas): array
    {
        return [
            'articulos' => $filas->count(),
            'agotados' => $filas->where('estado', 'agotado')->count(),
            'bajos' => $filas->where('estado', 'bajo')->count(),
            'ok' => $filas->where('estado', 'ok')->count(),
            'inactivos' => $filas->where('activo', false)->count(),
        ];
    }

    /**
     * Agrupa por categoría (alfabético, «Sin categoría» al final) con subtotales.
     *
     * @param  Collection<int, array<string, mixed>>  $filas
     * @return array<int, array{categoria: string, filas: array<int, array<string, mixed>>, subtotal: array<string, mixed>, agotados: int, bajos: int}>
     */
    private function agrupar(Collection $filas, callable $subtotal): array
    {
        return $filas->groupBy('categoria')
            ->sortKeysUsing(fn ($a, $b) => [$a === 'Sin categoría', $a] <=> [$b === 'Sin categoría', $b])
            ->map(fn (Collection $g, string $categoria) => [
                'categoria' => $categoria,
                'filas' => $g->values()->all(),
                'subtotal' => $subtotal($g),
                'agotados' => $g->where('estado', 'agotado')->count(),
                'bajos' => $g->where('estado', 'bajo')->count(),
            ])
            ->values()
            ->all();
    }
}

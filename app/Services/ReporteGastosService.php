<?php

namespace App\Services;

use App\Models\Gasto;
use App\Support\AgrupaPorPeriodo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Datos del reporte de gastos en PDF, a partir de la consulta ya filtrada del listado.
 * Los totales cuentan solo gastos registrados (no los anulados), igual que el listado.
 */
class ReporteGastosService
{
    use AgrupaPorPeriodo;

    /** Filas del detalle que se imprimen como máximo (dompdf se vuelve muy lento con más). */
    public const MAX_DETALLE = 1000;

    private const TOP_PROVEEDORES = 8;

    /**
     * @param  array{desde?: ?string, hasta?: ?string}  $filtros
     * @return array<string, mixed>
     */
    public function generar(Builder $consulta, array $filtros = []): array
    {
        $gastos = (clone $consulta)
            ->with('categoria')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $registrados = $gastos->where('estado', Gasto::ESTADO_REGISTRADO)->values();

        [$desde, $hasta] = $this->periodoDe($gastos, fn (Gasto $g) => $g->fecha, $filtros);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'kpis' => $this->kpis($registrados, $gastos),
            'por_periodo' => $this->agruparPorPeriodo($registrados, fn (Gasto $g) => $g->fecha, fn (Gasto $g) => (float) $g->monto, $desde, $hasta),
            'por_categoria' => $this->porCategoria($registrados),
            'top_proveedores' => $this->topProveedores($registrados),
            'detalle' => $gastos->take(self::MAX_DETALLE),
            'detalle_total' => $gastos->count(),
        ];
    }

    /**
     * @param  Collection<int, Gasto>  $registrados
     * @param  Collection<int, Gasto>  $todos
     * @return array<string, mixed>
     */
    private function kpis(Collection $registrados, Collection $todos): array
    {
        $total = round((float) $registrados->sum('monto'), 2);
        $count = $registrados->count();
        $compras = $registrados->filter(fn (Gasto $g) => $g->esAutomatico());
        $mayor = $registrados->sortByDesc('monto')->first();

        return [
            'count' => $count,
            'total' => $total,
            'promedio' => $count > 0 ? round($total / $count, 2) : 0.0,
            'compras_total' => round((float) $compras->sum('monto'), 2),
            'compras_count' => $compras->count(),
            'operativos_total' => round($total - (float) $compras->sum('monto'), 2),
            'operativos_count' => $count - $compras->count(),
            'mayor_descripcion' => $mayor?->descripcion,
            'mayor_monto' => $mayor ? round((float) $mayor->monto, 2) : 0.0,
            'categorias_count' => $registrados->pluck('categoria_gasto_id')->unique()->count(),
            'anulados_count' => $todos->where('estado', Gasto::ESTADO_ANULADO)->count(),
        ];
    }

    /**
     * @param  Collection<int, Gasto>  $registrados
     * @return array<int, array{categoria: string, count: int, total: float, participacion: float, automatica: bool}>
     */
    private function porCategoria(Collection $registrados): array
    {
        $granTotal = (float) $registrados->sum('monto');

        return $registrados->groupBy('categoria_gasto_id')
            ->map(function (Collection $gastos) use ($granTotal) {
                $categoria = $gastos->first()->categoria;
                $total = round((float) $gastos->sum('monto'), 2);

                return [
                    'categoria' => $categoria?->nombre ?? 'Sin categoría',
                    'count' => $gastos->count(),
                    'total' => $total,
                    'participacion' => $granTotal > 0 ? round($total / $granTotal * 100, 1) : 0.0,
                    'automatica' => (bool) $categoria?->esDelSistema(),
                ];
            })
            ->sortByDesc('total')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, Gasto>  $registrados
     * @return array<int, array{proveedor: string, count: int, total: float}>
     */
    private function topProveedores(Collection $registrados): array
    {
        return $registrados->filter(fn (Gasto $g) => filled($g->proveedor))
            ->groupBy(fn (Gasto $g) => mb_strtoupper(trim($g->proveedor)))
            ->map(fn (Collection $gastos) => [
                'proveedor' => trim($gastos->first()->proveedor),
                'count' => $gastos->count(),
                'total' => round((float) $gastos->sum('monto'), 2),
            ])
            ->sortByDesc('total')
            ->take(self::TOP_PROVEEDORES)
            ->values()
            ->all();
    }
}

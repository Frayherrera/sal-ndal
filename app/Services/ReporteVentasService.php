<?php

namespace App\Services;

use App\Models\Venta;
use App\Support\AgrupaPorPeriodo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Datos del reporte de ventas en PDF, a partir de la consulta ya filtrada del listado.
 * Los totales cuentan solo ventas confirmadas (igual que el resumen del listado).
 */
class ReporteVentasService
{
    use AgrupaPorPeriodo;

    /** Filas del detalle que se imprimen como máximo (dompdf se vuelve muy lento con más). */
    public const MAX_DETALLE = 1000;

    private const TOP_CLIENTES = 10;

    /**
     * @param  array{desde?: ?string, hasta?: ?string}  $filtros
     * @return array<string, mixed>
     */
    public function generar(Builder $consulta, array $filtros = []): array
    {
        $ventas = (clone $consulta)
            ->with('cliente')
            ->withSum('pagos', 'monto')
            ->orderBy('fecha')
            ->orderBy('id')
            ->get();

        $confirmadas = $ventas->where('estado', Venta::ESTADO_CONFIRMADA)->values();

        [$desde, $hasta] = $this->periodoDe($ventas, fn (Venta $v) => $v->fecha, $filtros);

        return [
            'desde' => $desde,
            'hasta' => $hasta,
            'kpis' => $this->kpis($confirmadas, $ventas),
            'por_periodo' => $this->agruparPorPeriodo($confirmadas, fn (Venta $v) => $v->fecha, fn (Venta $v) => (float) $v->total, $desde, $hasta),
            'top_clientes' => $this->topClientes($confirmadas),
            'detalle' => $ventas->take(self::MAX_DETALLE),
            'detalle_total' => $ventas->count(),
        ];
    }

    /**
     * @param  Collection<int, Venta>  $confirmadas
     * @param  Collection<int, Venta>  $todas
     * @return array<string, float|int>
     */
    private function kpis(Collection $confirmadas, Collection $todas): array
    {
        $total = round((float) $confirmadas->sum('total'), 2);
        $pagado = round($confirmadas->sum(fn (Venta $v) => $v->totalPagado()), 2);
        $contado = $confirmadas->where('forma_pago', Venta::FORMA_CONTADO);
        $credito = $confirmadas->where('forma_pago', '!=', Venta::FORMA_CONTADO);
        $count = $confirmadas->count();

        return [
            'count' => $count,
            'total' => $total,
            'pagado' => $pagado,
            'saldo' => round(max($total - $pagado, 0), 2),
            'ticket_promedio' => $count > 0 ? round($total / $count, 2) : 0.0,
            'contado_total' => round((float) $contado->sum('total'), 2),
            'contado_count' => $contado->count(),
            'credito_total' => round((float) $credito->sum('total'), 2),
            'credito_count' => $credito->count(),
            'vencidas_count' => $confirmadas->filter(fn (Venta $v) => $v->estaVencida())->count(),
            'clientes_count' => $confirmadas->pluck('cliente_id')->unique()->count(),
            'anuladas_count' => $todas->where('estado', Venta::ESTADO_ANULADA)->count(),
        ];
    }

    /**
     * @param  Collection<int, Venta>  $confirmadas
     * @return array<int, array{cliente: string, local: ?string, ciudad: ?string, count: int, total: float, saldo: float, participacion: float}>
     */
    private function topClientes(Collection $confirmadas): array
    {
        $granTotal = (float) $confirmadas->sum('total');

        return $confirmadas->groupBy('cliente_id')
            ->map(function (Collection $ventas) use ($granTotal) {
                $cliente = $ventas->first()->cliente;
                $total = round((float) $ventas->sum('total'), 2);

                return [
                    'cliente' => $cliente?->nombre ?? '—',
                    'local' => $cliente?->local,
                    'ciudad' => $cliente?->ciudad,
                    'count' => $ventas->count(),
                    'total' => $total,
                    'saldo' => round($ventas->sum(fn (Venta $v) => max($v->saldo(), 0)), 2),
                    'participacion' => $granTotal > 0 ? round($total / $granTotal * 100, 1) : 0.0,
                ];
            })
            ->sortByDesc('total')
            ->take(self::TOP_CLIENTES)
            ->values()
            ->all();
    }
}

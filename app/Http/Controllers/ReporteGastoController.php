<?php

namespace App\Http\Controllers;

use App\Models\Gasto;
use App\Models\PagoVenta;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReporteGastoController extends Controller
{
    /**
     * Totales de gastos por categoría en un rango de fechas (por defecto, el mes actual).
     */
    public function categorias(Request $request): View
    {
        $desde = $request->input('desde', now()->startOfMonth()->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());

        $filas = Gasto::query()
            ->where('estado', Gasto::ESTADO_REGISTRADO)
            ->when($desde, fn (Builder $q) => $q->whereDate('fecha', '>=', $desde))
            ->when($hasta, fn (Builder $q) => $q->whereDate('fecha', '<=', $hasta))
            ->select('categoria_gasto_id')
            ->selectRaw('COUNT(*) as gastos_count')
            ->selectRaw('SUM(monto) as total')
            ->with('categoria')
            ->groupBy('categoria_gasto_id')
            ->orderByDesc('total')
            ->get();

        $total = round((float) $filas->sum('total'), 2);

        return view('gastos.reportes.categorias', compact('filas', 'total', 'desde', 'hasta'));
    }

    /**
     * Ventas, cobros y gastos por mes de un año, con la diferencia (ventas − gastos).
     */
    public function resultado(Request $request): View
    {
        $anio = (int) $request->input('anio', now()->year);

        if ($anio < 2000 || $anio > 2100) {
            $anio = now()->year;
        }

        // Se agrupa en PHP para no depender de funciones de fecha propias de cada motor de BD
        $porMes = fn ($registros, string $campoFecha, string $campoValor) => $registros
            ->groupBy(fn ($r) => (int) $r->{$campoFecha}->format('n'))
            ->map(fn ($grupo) => round((float) $grupo->sum($campoValor), 2));

        $ventas = $porMes(
            Venta::where('estado', Venta::ESTADO_CONFIRMADA)->whereYear('fecha', $anio)->get(['fecha', 'total']),
            'fecha',
            'total'
        );

        $cobrado = $porMes(
            PagoVenta::whereHas('venta', fn (Builder $q) => $q->where('estado', Venta::ESTADO_CONFIRMADA))
                ->whereYear('fecha', $anio)
                ->get(['fecha', 'monto']),
            'fecha',
            'monto'
        );

        $gastos = $porMes(
            Gasto::where('estado', Gasto::ESTADO_REGISTRADO)->whereYear('fecha', $anio)->get(['fecha', 'monto']),
            'fecha',
            'monto'
        );

        $meses = collect(range(1, 12))->map(function (int $mes) use ($anio, $ventas, $cobrado, $gastos) {
            $venta = $ventas[$mes] ?? 0.0;
            $gasto = $gastos[$mes] ?? 0.0;

            return [
                'mes' => $mes,
                'nombre' => ucfirst(now()->setDate($anio, $mes, 1)->locale('es')->translatedFormat('F')),
                'ventas' => $venta,
                'cobrado' => $cobrado[$mes] ?? 0.0,
                'gastos' => $gasto,
                'diferencia' => round($venta - $gasto, 2),
            ];
        });

        $totales = [
            'ventas' => round($meses->sum('ventas'), 2),
            'cobrado' => round($meses->sum('cobrado'), 2),
            'gastos' => round($meses->sum('gastos'), 2),
            'diferencia' => round($meses->sum('diferencia'), 2),
        ];

        return view('gastos.reportes.resultado', compact('meses', 'totales', 'anio'));
    }
}

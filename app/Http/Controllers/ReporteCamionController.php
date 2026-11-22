<?php

namespace App\Http\Controllers;

use App\Models\DetalleViajeCamion;
use App\Models\Ruta;
use App\Models\ViajeCamion;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ReporteCamionController extends Controller
{
    /**
     * Qué se vendió por semana: una columna por viaje cerrado y una fila por producto.
     */
    public function semanal(Request $request): View
    {
        $desde = $request->input('desde', now()->subWeeks(8)->toDateString());
        $hasta = $request->input('hasta', now()->toDateString());
        $rutaId = $request->filled('ruta_id') ? (int) $request->input('ruta_id') : null;

        $viajes = ViajeCamion::with(['ruta', 'detalles.producto'])
            ->where('estado', ViajeCamion::CERRADO)
            ->whereDate('fecha_salida', '>=', $desde)
            ->whereDate('fecha_salida', '<=', $hasta)
            ->when($rutaId, fn ($q) => $q->where('ruta_id', $rutaId))
            ->orderBy('fecha_salida')->orderBy('id')
            ->get();

        $lineas = $viajes->flatMap->detalles;

        // Fila por producto: vendido en cada viaje + totales
        $filas = $lineas->groupBy('producto_terminado_id')
            ->map(function (Collection $porProducto) {
                $producto = $porProducto->first()->producto;

                return [
                    'producto' => $producto,
                    'por_viaje' => $porProducto->mapWithKeys(fn (DetalleViajeCamion $d) => [$d->viaje_camion_id => (int) $d->vendido])->all(),
                    'vendido' => (int) $porProducto->sum('vendido'),
                    'valor' => round($porProducto->sum(fn (DetalleViajeCamion $d) => $d->valorVendido()), 2),
                    'merma' => (int) $porProducto->sum(fn (DetalleViajeCamion $d) => ($d->danado ?? 0) + ($d->regalado ?? 0)),
                ];
            })
            ->sortByDesc('vendido')
            ->values();

        // Totales por viaje (columna)
        $totales = $viajes->mapWithKeys(fn (ViajeCamion $v) => [$v->id => [
            'vendido' => (int) $v->detalles->sum('vendido'),
            'valor' => round($v->detalles->sum(fn (DetalleViajeCamion $d) => $d->valorVendido()), 2),
            'danado' => (int) $v->detalles->sum('danado'),
            'regalado' => (int) $v->detalles->sum('regalado'),
            'merma_valor' => round($v->detalles->sum(fn (DetalleViajeCamion $d) => $d->valorMerma()), 2),
        ]]);

        return view('camion.reportes.semanal', [
            'viajes' => $viajes,
            'filas' => $filas,
            'totales' => $totales,
            'rutas' => Ruta::orderBy('orden')->get(),
            'desde' => $desde,
            'hasta' => $hasta,
            'rutaId' => $rutaId,
            'granTotal' => [
                'vendido' => (int) $lineas->sum('vendido'),
                'valor' => round($totales->sum('valor'), 2),
                'merma_valor' => round($totales->sum('merma_valor'), 2),
            ],
        ]);
    }
}

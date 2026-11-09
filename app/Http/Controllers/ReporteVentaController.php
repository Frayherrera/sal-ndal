<?php

namespace App\Http\Controllers;

use App\Models\DetalleVenta;
use App\Models\PagoVenta;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReporteVentaController extends Controller
{
    public function clientes(Request $request): View
    {
        $ventas = Venta::query()
            ->where('estado', Venta::ESTADO_CONFIRMADA)
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('fecha', '<=', $request->hasta));

        $filas = (clone $ventas)
            ->select('cliente_id')
            ->selectRaw('COUNT(*) as ventas_count')
            ->selectRaw('SUM(total) as total_vendido')
            ->with('cliente')
            ->groupBy('cliente_id')
            ->orderByDesc('total_vendido')
            ->paginate(25)
            ->withQueryString();

        $pagos = PagoVenta::query()
            ->join('ventas', 'ventas.id', '=', 'pago_venta.venta_id')
            ->where('ventas.estado', Venta::ESTADO_CONFIRMADA)
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('ventas.fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('ventas.fecha', '<=', $request->hasta))
            ->groupBy('ventas.cliente_id')
            ->selectRaw('ventas.cliente_id, SUM(pago_venta.monto) as total_pagado')
            ->get()
            ->mapWithKeys(fn ($fila) => [$fila->cliente_id => (float) $fila->total_pagado]);

        $filas->getCollection()->transform(function ($fila) use ($pagos) {
            $pagado = $pagos[$fila->cliente_id] ?? 0;
            $fila->total_pagado = $pagado;
            $fila->saldo = round((float) $fila->total_vendido - $pagado, 2);

            return $fila;
        });

        return view('ventas.reportes.clientes', compact('filas'));
    }

    public function productos(Request $request): View
    {
        $filas = DetalleVenta::query()
            ->join('ventas', 'ventas.id', '=', 'detalle_venta.venta_id')
            ->join('producto_terminados', 'producto_terminados.id', '=', 'detalle_venta.producto_terminado_id')
            ->where('ventas.estado', Venta::ESTADO_CONFIRMADA)
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('ventas.fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('ventas.fecha', '<=', $request->hasta))
            ->groupBy('detalle_venta.producto_terminado_id', 'producto_terminados.nombre', 'producto_terminados.codigo')
            ->selectRaw('producto_terminados.id as producto_id, producto_terminados.nombre, producto_terminados.codigo')
            ->selectRaw('SUM(detalle_venta.cantidad_unidades) as unidades')
            ->selectRaw('SUM(detalle_venta.total) as ingresos')
            ->orderByDesc('ingresos')
            ->paginate(25)
            ->withQueryString();

        return view('ventas.reportes.productos', compact('filas'));
    }
}

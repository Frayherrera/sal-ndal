<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Venta;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarteraController extends Controller
{
    public function index(Request $request): View
    {
        $saldoPendiente = 'ventas.total > (select coalesce(sum(pv.monto), 0) from pago_venta pv where pv.venta_id = ventas.id)';

        $base = Venta::query()
            ->where('estado', Venta::ESTADO_CONFIRMADA)
            ->whereRaw($saldoPendiente)
            ->when($request->filled('cliente_id'), fn ($q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->boolean('vencidas'), fn ($q) => $q
                ->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '<', now()->toDateString()));

        $todas = (clone $base)->withSum('pagos', 'monto')->get();

        $resumen = [
            'total' => round($todas->sum(fn (Venta $venta) => $venta->saldo()), 2),
            'count' => $todas->count(),
            'vencidas' => $todas->filter(fn (Venta $venta) => $venta->estaVencida())->count(),
        ];

        $ventas = $base
            ->with('cliente')
            ->withSum('pagos', 'monto')
            ->orderByRaw('fecha_vencimiento is null')
            ->orderBy('fecha_vencimiento')
            ->orderBy('fecha')
            ->paginate(20)
            ->withQueryString();

        return view('ventas.cartera', [
            'ventas' => $ventas,
            'clientes' => Cliente::orderBy('nombre')->get(),
            'resumen' => $resumen,
        ]);
    }
}

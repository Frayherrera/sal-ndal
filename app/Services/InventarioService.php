<?php

namespace App\Services;

use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\PagoVenta;
use App\Models\ProductoTerminado;
use App\Models\Venta;

class InventarioService
{
    public function resumen(): array
    {
        $materiasPrimasActivas = MateriaPrima::where('activo', true)->get();

        $stockMateriaPrimaKg = $materiasPrimasActivas->sum(fn ($mp) => $mp->stock_kg());

        $productos = ProductoTerminado::where('activo', true)->with('inventario')->get();
        $productosDisponibles = $productos->sum(fn ($pt) => $pt->stock_disponible());

        $productosBajos = $productos->filter(fn ($pt) => $pt->esStockBajo())->count();
        $materiaBaja = $materiasPrimasActivas->filter(fn ($mp) => $mp->esStockBajo())->count();

        $movimientosRecientes = MovimientoInventario::with(['user', 'origen'])
            ->latest('fecha')
            ->limit(8)
            ->get();

        $ventasHoy = Venta::where('estado', Venta::ESTADO_CONFIRMADA)
            ->whereDate('fecha', now()->toDateString());

        $ventasMes = Venta::where('estado', Venta::ESTADO_CONFIRMADA)
            ->whereYear('fecha', now()->year)
            ->whereMonth('fecha', now()->month);

        $totalConfirmado = (float) Venta::where('estado', Venta::ESTADO_CONFIRMADA)->sum('total');
        $totalPagado = (float) PagoVenta::whereHas('venta', fn ($q) => $q->where('estado', Venta::ESTADO_CONFIRMADA))->sum('monto');

        $carteraVencida = Venta::where('estado', Venta::ESTADO_CONFIRMADA)
            ->whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', now()->toDateString())
            ->withSum('pagos', 'monto')
            ->get()
            ->filter(fn (Venta $venta) => $venta->saldo() > 0)
            ->count();

        return [
            'stock_materia_prima_kg' => $stockMateriaPrimaKg,
            'materias_primas_count' => $materiasPrimasActivas->count(),
            'materias_primas_bajas' => $materiaBaja,
            'productos_disponibles' => $productosDisponibles,
            'productos_count' => $productos->count(),
            'productos_bajos' => $productosBajos,
            'movimientos_recientes' => $movimientosRecientes,
            'ventas_hoy_total' => (float) (clone $ventasHoy)->sum('total'),
            'ventas_hoy_count' => (clone $ventasHoy)->count(),
            'ventas_mes_total' => (float) (clone $ventasMes)->sum('total'),
            'ventas_mes_count' => (clone $ventasMes)->count(),
            'cartera_pendiente' => round(max($totalConfirmado - $totalPagado, 0), 2),
            'cartera_vencidas' => $carteraVencida,
        ];
    }

    public function alertasStockBajo(): array
    {
        $mpBajas = MateriaPrima::where('activo', true)
            ->with('inventario')
            ->get()
            ->filter(fn ($mp) => $mp->esStockBajo());

        $ptBajos = ProductoTerminado::where('activo', true)
            ->with('inventario')
            ->get()
            ->filter(fn ($pt) => $pt->esStockBajo());

        return [
            'materias_primas' => $mpBajas,
            'productos' => $ptBajos,
        ];
    }
}

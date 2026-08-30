<?php

namespace App\Services;

use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;

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

        return [
            'stock_materia_prima_kg' => $stockMateriaPrimaKg,
            'materias_primas_count' => $materiasPrimasActivas->count(),
            'materias_primas_bajas' => $materiaBaja,
            'productos_disponibles' => $productosDisponibles,
            'productos_count' => $productos->count(),
            'productos_bajos' => $productosBajos,
            'movimientos_recientes' => $movimientosRecientes,
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

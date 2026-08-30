<?php

namespace App\Http\Controllers;

use App\Models\ProductoTerminado;
use App\Services\MovimientoInventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProduccionController extends Controller
{
    public function __construct(protected MovimientoInventarioService $service) {}

    public function create(): View
    {
        $productos = ProductoTerminado::where('activo', true)
            ->with('receta')
            ->orderBy('nombre')
            ->get();

        return view('inventario.produccion.create', compact('productos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_terminado_id' => ['required', 'exists:producto_terminados,id'],
            'cantidad' => ['required', 'integer', 'gt:0'],
            'documento' => ['nullable', 'string', 'max:50'],
            'motivo' => ['nullable', 'string', 'max:500'],
            'fecha' => ['nullable', 'date'],
        ]);

        try {
            $producto = ProductoTerminado::findOrFail($data['producto_terminado_id']);
            $movimientos = $this->service->producir(
                $producto,
                (int) $data['cantidad'],
                [
                    'documento' => $data['documento'] ?? null,
                    'motivo' => $data['motivo'] ?? null,
                    'fecha' => $data['fecha'] ?? now(),
                ]
            );

            return redirect()->route('inventario.movimientos.index')
                ->with('success', "Producción registrada: {$data['cantidad']} unidad(es) de \"{$producto->nombre}\". Materias primas descontadas.")
                ->with('movimientosProduccion', count($movimientos));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}

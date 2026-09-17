<?php

namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Services\MovimientoInventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MolidoController extends Controller
{
    public function __construct(protected MovimientoInventarioService $service) {}

    public function create(MateriaPrima $materia): View|RedirectResponse
    {
        if (! $materia->es_molido) {
            return redirect()->route('inventario.materia-prima.index')
                ->with('error', 'Esa materia prima no está marcada como molido.');
        }

        $materia->load(['detalleMolido.ingrediente', 'inventario']);

        return view('inventario.materia-prima.producir-molido', compact('materia'));
    }

    public function store(Request $request, MateriaPrima $materia): RedirectResponse
    {
        if (! $materia->es_molido) {
            return redirect()->route('inventario.materia-prima.index')
                ->with('error', 'Esa materia prima no está marcada como molido.');
        }

        $data = $request->validate([
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'documento' => ['nullable', 'string', 'max:50'],
            'motivo' => ['nullable', 'string', 'max:500'],
            'fecha' => ['nullable', 'date'],
        ]);

        $gramos = $materia->unidad_base === 'kg'
            ? (int) round((float) $data['cantidad'] * 1000)
            : (int) round((float) $data['cantidad']);

        try {
            $this->service->producirMolido($materia, $gramos, [
                'documento' => $data['documento'] ?? null,
                'motivo' => $data['motivo'] ?? null,
                'fecha' => $data['fecha'] ?? now(),
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('inventario.materia-prima.show', $materia)
            ->with('success', "Molienda registrada: {$data['cantidad']} {$materia->unidad_base} de \"{$materia->nombre}\". Ingredientes descontados.");
    }
}

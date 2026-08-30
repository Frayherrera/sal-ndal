<?php

namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Services\MateriaPrimaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MateriaPrimaController extends Controller
{
    public function __construct(protected MateriaPrimaService $service) {}

    public function index(): View
    {
        $materiasPrimas = MateriaPrima::with('inventario')->orderBy('nombre')->get();

        return view('inventario.materia-prima.index', compact('materiasPrimas'));
    }

    public function create(): View
    {
        return view('inventario.materia-prima.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:30', 'unique:materias_primas,codigo'],
            'nombre' => ['required', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'unidad_base' => ['required', 'in:kg,g'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:120'],
            'ubicacion' => ['nullable', 'string', 'max:120'],
        ]);

        $this->service->crear($data);

        return redirect()->route('inventario.materia-prima.index')
            ->with('success', 'Materia prima registrada correctamente.');
    }

    public function show(MateriaPrima $materia): View
    {
        $materia->load(['inventario', 'movimientos' => fn ($q) => $q->latest('fecha')]);

        return view('inventario.materia-prima.show', compact('materia'));
    }

    public function edit(MateriaPrima $materia): View
    {
        return view('inventario.materia-prima.edit', compact('materia'));
    }

    public function update(Request $request, MateriaPrima $materia): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:30', 'unique:materias_primas,codigo,'.$materia->id],
            'nombre' => ['required', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'unidad_base' => ['required', 'in:kg,g'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'proveedor' => ['nullable', 'string', 'max:120'],
            'ubicacion' => ['nullable', 'string', 'max:120'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $this->service->actualizar($materia, $data);

        return redirect()->route('inventario.materia-prima.index')
            ->with('success', 'Materia prima actualizada correctamente.');
    }

    public function toggleActivo(MateriaPrima $materia): RedirectResponse
    {
        $this->service->toggleActivo($materia);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(MateriaPrima $materia): RedirectResponse
    {
        try {
            $this->service->eliminar($materia);

            return redirect()->route('inventario.materia-prima.index')
                ->with('success', 'Materia prima eliminada.');
        } catch (\RuntimeException $e) {
            return redirect()->route('inventario.materia-prima.index')
                ->with('error', $e->getMessage());
        }
    }
}

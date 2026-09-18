<?php

namespace App\Http\Controllers;

use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use App\Services\MateriaPrimaService;
use App\Services\MolidoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class MateriaPrimaController extends Controller
{
    public function __construct(
        protected MateriaPrimaService $service,
        protected MolidoService $molidoService,
    ) {}

    public function index(): View
    {
        $direccion = request('dir') === 'asc' ? 'asc' : 'desc';
        $query = MateriaPrima::with('inventario');

        if (request('sort') === 'stock') {
            $query->orderBy(
                InventarioMateriaPrima::select('stock_gramos')
                    ->whereColumn('inventario_materia_prima.materia_prima_id', 'materias_primas.id'),
                $direccion
            );
        } else {
            $query->orderBy('nombre');
        }

        return view('inventario.materia-prima.index', [
            'materiasPrimas' => $query->get(),
            'sort' => request('sort'),
            'dir' => request('dir'),
        ]);
    }

    public function create(): View
    {
        $ingredientes = $this->molidoService->ingredientesDisponibles();

        return view('inventario.materia-prima.create', compact('ingredientes'));
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
            'es_molido' => ['sometimes', 'boolean'],
            'lineas' => ['nullable', 'array'],
            'lineas.*.ingrediente_id' => ['nullable', 'exists:materias_primas,id'],
            'lineas.*.gramos_por_kg' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $lineas = $data['lineas'] ?? [];
        unset($data['lineas']);
        $data['es_molido'] = $request->boolean('es_molido');

        try {
            $this->service->crear($data, $lineas);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('inventario.materia-prima.index')
            ->with('success', 'Materia prima registrada correctamente.');
    }

    public function show(MateriaPrima $materia): View
    {
        $materia->load([
            'inventario',
            'detalleMolido.ingrediente',
            'movimientos' => fn ($q) => $q->latest('fecha'),
        ]);

        return view('inventario.materia-prima.show', compact('materia'));
    }

    public function edit(MateriaPrima $materia): View
    {
        $ingredientes = $this->molidoService->ingredientesDisponibles($materia);

        return view('inventario.materia-prima.edit', compact('materia', 'ingredientes'));
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
            'stock' => ['nullable', 'numeric', 'min:0'],
            'es_molido' => ['sometimes', 'boolean'],
            'lineas' => ['nullable', 'array'],
            'lineas.*.ingrediente_id' => ['nullable', 'exists:materias_primas,id'],
            'lineas.*.gramos_por_kg' => ['nullable', 'numeric', 'gt:0'],
        ]);

        $lineas = $data['lineas'] ?? [];
        unset($data['lineas']);
        $data['es_molido'] = $request->boolean('es_molido');

        $unidadOriginal = $materia->unidad_base === 'kg' ? 'kg' : 'g';
        $unidadNueva = $request->input('unidad_base');

        try {
            $this->service->actualizar($materia, $data, $lineas);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        if (isset($data['stock']) && $unidadNueva === $unidadOriginal) {
            $nuevosGramos = $unidadNueva === 'kg'
                ? (int) round($data['stock'] * 1000)
                : (int) $data['stock'];

            $this->service->actualizarStock($materia, $nuevosGramos);
        }

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
        } catch (RuntimeException $e) {
            return redirect()->route('inventario.materia-prima.index')
                ->with('error', $e->getMessage());
        }
    }
}

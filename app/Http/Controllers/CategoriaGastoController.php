<?php

namespace App\Http\Controllers;

use App\Models\CategoriaGasto;
use App\Services\CategoriaGastoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use RuntimeException;

class CategoriaGastoController extends Controller
{
    public function __construct(protected CategoriaGastoService $service) {}

    public function index(): View
    {
        return view('gastos.categorias.index', [
            'categorias' => CategoriaGasto::withCount('gastos')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('gastos.categorias.create', ['categoria' => new CategoriaGasto(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->service->crear($this->validar($request));

        return redirect()->route('gastos.categorias.index')
            ->with('success', 'Categoría creada correctamente.');
    }

    public function edit(CategoriaGasto $categoria): View
    {
        return view('gastos.categorias.edit', compact('categoria'));
    }

    public function update(Request $request, CategoriaGasto $categoria): RedirectResponse
    {
        try {
            $this->service->actualizar($categoria, $this->validar($request, $categoria));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.categorias.index')
            ->with('success', 'Categoría actualizada.');
    }

    public function destroy(CategoriaGasto $categoria): RedirectResponse
    {
        try {
            $this->service->eliminar($categoria);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.categorias.index')
            ->with('success', 'Categoría eliminada.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?CategoriaGasto $categoria = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('categorias_gasto', 'nombre')->ignore($categoria?->id)],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.unique' => 'Ya existe una categoría con ese nombre.',
            'nombre.max' => 'El nombre no puede superar los 80 caracteres.',
        ]);

        $data['activo'] = $request->boolean('activo');

        return $data;
    }
}

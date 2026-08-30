<?php

namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Services\RecetaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class RecetaController extends Controller
{
    public function __construct(protected RecetaService $service) {}

    public function index(): View
    {
        $productos = ProductoTerminado::with('receta')
            ->orderBy('nombre')
            ->get();

        return view('inventario.recetas.index', compact('productos'));
    }

    public function create(): View
    {
        // Solo productos sin receta (y activos) pueden crear una receta nueva.
        $productos = ProductoTerminado::where('activo', true)
            ->whereDoesntHave('receta')
            ->orderBy('nombre')
            ->get();
        $materiasPrimas = MateriaPrima::where('activo', true)->orderBy('nombre')->get();

        return view('inventario.recetas.create', compact('productos', 'materiasPrimas'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_terminado_id' => ['required', 'exists:producto_terminados,id'],
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.materia_prima_id' => ['required', 'exists:materias_primas,id'],
            'lineas.*.gramos_por_unidad' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $producto = ProductoTerminado::findOrFail($data['producto_terminado_id']);
            $this->service->guardar($producto, $data['lineas']);

            return redirect()->route('inventario.recetas.edit', $producto)
                ->with('success', "Receta de \"{$producto->nombre}\" guardada correctamente.");
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function edit(ProductoTerminado $producto): View
    {
        $producto->load('receta.materiaPrima');
        $materiasPrimas = MateriaPrima::where('activo', true)->orderBy('nombre')->get();

        return view('inventario.recetas.edit', compact('producto', 'materiasPrimas'));
    }

    public function update(Request $request, ProductoTerminado $producto): RedirectResponse
    {
        $data = $request->validate([
            'lineas' => ['required', 'array', 'min:1'],
            'lineas.*.materia_prima_id' => ['required', 'exists:materias_primas,id'],
            'lineas.*.gramos_por_unidad' => ['required', 'numeric', 'gt:0'],
        ]);

        try {
            $this->service->guardar($producto, $data['lineas']);

            return redirect()->route('inventario.recetas.edit', $producto)
                ->with('success', "Receta de \"{$producto->nombre}\" actualizada correctamente.");
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function destroy(ProductoTerminado $producto): RedirectResponse
    {
        $this->service->eliminar($producto);

        return redirect()->route('inventario.recetas.index')
            ->with('success', "Receta de \"{$producto->nombre}\" eliminada.");
    }
}

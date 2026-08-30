<?php

namespace App\Http\Controllers;

use App\Models\ProductoTerminado;
use App\Services\ProductoTerminadoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductoTerminadoController extends Controller
{
    public function __construct(protected ProductoTerminadoService $service) {}

    public function index(): View
    {
        $productos = ProductoTerminado::with('inventario')->orderBy('nombre')->get();

        return view('inventario.productos-terminados.index', compact('productos'));
    }

    public function create(): View
    {
        return view('inventario.productos-terminados.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:30', 'unique:producto_terminados,codigo'],
            'nombre' => ['required', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'presentacion' => ['nullable', 'in:bolsa,frasco'],
            'peso_neto' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'imagen' => ['nullable', 'string', 'max:255'],
        ]);

        $this->service->crear($data);

        return redirect()->route('inventario.productos-terminados.index')
            ->with('success', 'Producto terminado registrado correctamente.');
    }

    public function show(ProductoTerminado $producto): View
    {
        $producto->load(['inventario', 'receta.materiaPrima', 'movimientos' => fn ($q) => $q->latest('fecha')]);

        return view('inventario.productos-terminados.show', compact('producto'));
    }

    public function edit(ProductoTerminado $producto): View
    {
        return view('inventario.productos-terminados.edit', compact('producto'));
    }

    public function update(Request $request, ProductoTerminado $producto): RedirectResponse
    {
        $data = $request->validate([
            'codigo' => ['nullable', 'string', 'max:30', 'unique:producto_terminados,codigo,'.$producto->id],
            'nombre' => ['required', 'string', 'max:120'],
            'categoria' => ['nullable', 'string', 'max:80'],
            'presentacion' => ['nullable', 'in:bolsa,frasco'],
            'peso_neto' => ['nullable', 'numeric', 'min:0'],
            'precio_venta' => ['nullable', 'numeric', 'min:0'],
            'stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'imagen' => ['nullable', 'string', 'max:255'],
            'activo' => ['sometimes', 'boolean'],
        ]);

        $this->service->actualizar($producto, $data);

        return redirect()->route('inventario.productos-terminados.index')
            ->with('success', 'Producto terminado actualizado correctamente.');
    }

    public function toggleActivo(ProductoTerminado $producto): RedirectResponse
    {
        $this->service->toggleActivo($producto);

        return back()->with('success', 'Estado actualizado.');
    }

    public function destroy(ProductoTerminado $producto): RedirectResponse
    {
        try {
            $this->service->eliminar($producto);

            return redirect()->route('inventario.productos-terminados.index')
                ->with('success', 'Producto terminado eliminado.');
        } catch (\RuntimeException $e) {
            return redirect()->route('inventario.productos-terminados.index')
                ->with('error', $e->getMessage());
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Producto;
use App\Models\ProductoTerminado;
use App\Services\ProductoTerminadoService;
use App\Services\ReporteInventarioService;
use App\Support\Paquetes;
use App\Support\PdfReporte;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;

/**
 * Productos terminados: cada Producto agrupa sus presentaciones (B2C / Especial).
 */
class ProductoTerminadoController extends Controller
{
    public function __construct(protected ProductoTerminadoService $service) {}

    public function index(): View
    {
        $productos = Producto::with('presentaciones.inventario')->orderBy('nombre')->get();

        if (request('sort') === 'stock') {
            $productos = $productos->sortBy(fn (Producto $p) => $p->stock(), SORT_REGULAR, request('dir') !== 'asc')->values();
        }

        return view('inventario.productos-terminados.index', [
            'productos' => $productos,
            'sort' => request('sort'),
            'dir' => request('dir'),
        ]);
    }

    /**
     * Reporte PDF del inventario actual de producto terminado (bodega y camión).
     */
    public function exportPdf(ReporteInventarioService $reportes): Response
    {
        return PdfReporte::descargar(
            'inventario.productos-terminados.reporte-pdf',
            $reportes->productoTerminado(),
            'Inventario de producto terminado',
            'inventario-producto-terminado',
        );
    }

    public function create(): View
    {
        return view('inventario.productos-terminados.create', ['producto' => new Producto(['activo' => true])]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        $producto = $this->service->guardarProducto(null, $data['nombre'], $data['presentaciones']);

        return redirect()->route('inventario.productos-terminados.show', $producto)
            ->with('success', "Producto \"{$producto->nombre}\" registrado con {$producto->presentaciones->count()} presentación(es).");
    }

    public function show(Request $request, Producto $producto): View
    {
        $producto->load(['presentaciones.inventario', 'presentaciones.receta.materiaPrima']);

        // Kardex de la presentación elegida (por defecto la primera)
        $seleccionada = $producto->presentaciones->firstWhere('id', (int) $request->query('presentacion'))
            ?? $producto->presentaciones->first();
        $movimientos = $seleccionada?->movimientos()->latest('fecha')->latest('id')->get() ?? collect();

        return view('inventario.productos-terminados.show', compact('producto', 'seleccionada', 'movimientos'));
    }

    public function edit(Producto $producto): View
    {
        $producto->load('presentaciones');

        return view('inventario.productos-terminados.edit', compact('producto'));
    }

    public function update(Request $request, Producto $producto): RedirectResponse
    {
        $data = $this->validar($request, $producto);

        $this->service->guardarProducto($producto, $data['nombre'], $data['presentaciones'], (bool) ($data['activo'] ?? $producto->activo));

        return redirect()->route('inventario.productos-terminados.show', $producto)
            ->with('success', 'Producto actualizado correctamente.');
    }

    public function toggleActivo(Producto $producto): RedirectResponse
    {
        $this->service->toggleProducto($producto);

        return back()->with('success', 'Estado actualizado.');
    }

    /** Ajuste manual del stock de una presentación (queda en el kardex). */
    public function ajustarStock(Request $request, ProductoTerminado $presentacion): RedirectResponse
    {
        $data = $request->validate([
            'paquetes' => ['nullable', 'integer', 'min:0'],
            'sueltas' => ['nullable', 'integer', 'min:0'],
            'motivo' => ['required', 'string', 'max:500'],
        ], [
            'motivo.required' => 'Escribe el motivo del ajuste.',
        ]);

        $nuevo = Paquetes::aUnidades((int) ($data['paquetes'] ?? 0), (int) ($data['sueltas'] ?? 0));
        $anterior = $presentacion->stock_disponible();

        try {
            $movimiento = $this->service->ajustarStock($presentacion, $nuevo, $data['motivo']);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $volver = redirect()->route('inventario.productos-terminados.show', [$presentacion->producto_id, 'presentacion' => $presentacion->id]);

        return $movimiento
            ? $volver->with('success', "Stock de {$presentacion->nombreCompleto()} ajustado de ".Paquetes::texto($anterior).' a '.Paquetes::texto($nuevo).'.')
            : $volver->with('success', 'El stock ya era '.Paquetes::texto($nuevo).'; no se registró ningún ajuste.');
    }

    /** Pantalla de confirmación: muestra el historial que se borrará. */
    public function confirmarEliminar(Producto $producto): View
    {
        $producto->load('presentaciones.inventario');
        $resumen = $this->service->resumenEliminacion($producto);

        return view('inventario.productos-terminados.eliminar', compact('producto', 'resumen'));
    }

    public function destroy(Request $request, Producto $producto): RedirectResponse
    {
        // Con historial se exige marcar la casilla de confirmación
        if ($this->service->resumenEliminacion($producto)['historial']) {
            $request->validate(
                ['confirmar' => 'accepted'],
                ['confirmar.accepted' => 'Marca la casilla para confirmar que se borrará el historial.'],
            );
        }

        try {
            $nombre = $producto->nombre;
            $this->service->eliminarProducto($producto);

            return redirect()->route('inventario.productos-terminados.index')
                ->with('success', "Producto \"{$nombre}\" eliminado con todo su historial.");
        } catch (RuntimeException $e) {
            return redirect()->route('inventario.productos-terminados.index')
                ->with('error', $e->getMessage());
        }
    }

    /**
     * @return array{nombre: string, activo?: bool, presentaciones: array<int, array<string, mixed>>}
     */
    private function validar(Request $request, ?Producto $producto = null): array
    {
        // Filas vacías del formulario (sin peso, precio ni código) no cuentan
        $request->merge(['presentaciones' => array_values(array_filter(
            (array) $request->input('presentaciones', []),
            fn ($p) => is_array($p) && (filled($p['id'] ?? null) || filled($p['peso_neto'] ?? null)
                || filled($p['precio_venta'] ?? null) || filled($p['codigo'] ?? null)),
        ))]);

        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:120', Rule::unique('productos', 'nombre')->ignore($producto?->id)],
            'activo' => ['sometimes', 'boolean'],
            'presentaciones' => ['required', 'array', 'min:1'],
            'presentaciones.*.id' => ['nullable', 'integer'],
            'presentaciones.*.categoria' => ['required', Rule::in(array_keys(ProductoTerminado::LINEAS))],
            'presentaciones.*.codigo' => ['nullable', 'string', 'max:30'],
            'presentaciones.*.presentacion' => ['nullable', 'in:bolsa,frasco'],
            'presentaciones.*.peso_neto' => ['nullable', 'numeric', 'min:0'],
            'presentaciones.*.precio_venta' => ['nullable', 'numeric', 'min:0'],
            'presentaciones.*.stock_minimo' => ['nullable', 'numeric', 'min:0'],
            'presentaciones.*.activo' => ['nullable', 'boolean'],
        ], [
            'nombre.unique' => 'Ya existe un producto con ese nombre.',
            'presentaciones.required' => 'Agrega al menos una presentación.',
            'presentaciones.*.categoria.in' => 'La línea debe ser B2C o Especial.',
        ]);

        // Códigos únicos: entre las filas y frente a las demás presentaciones
        $usados = [];
        foreach ($data['presentaciones'] as $i => $p) {
            $codigo = trim((string) ($p['codigo'] ?? ''));
            if ($codigo === '') {
                continue;
            }
            $ocupado = ProductoTerminado::where('codigo', $codigo)
                ->when(filled($p['id'] ?? null), fn ($q) => $q->whereKeyNot($p['id']))
                ->exists();
            if ($ocupado || isset($usados[$codigo])) {
                throw ValidationException::withMessages(["presentaciones.$i.codigo" => "El código {$codigo} ya está en uso."]);
            }
            $usados[$codigo] = true;
        }

        // El formulario envía activo=0/1 (campo oculto + casilla); si no viene, queda activa
        foreach ($data['presentaciones'] as $i => $p) {
            $data['presentaciones'][$i]['activo'] = (bool) ($p['activo'] ?? true);
        }

        return $data;
    }
}

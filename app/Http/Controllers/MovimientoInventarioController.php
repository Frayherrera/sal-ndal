<?php

namespace App\Http\Controllers;

use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
use App\Services\MovimientoInventarioService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MovimientoInventarioController extends Controller
{
    public function __construct(protected MovimientoInventarioService $service) {}

    public function index(Request $request): View
    {
        $tipos = MovimientoInventario::TIPOS;

        $movimientos = MovimientoInventario::with(['user', 'origen'])
            ->when($request->filled('tipo'), fn ($q) => $q->where('tipo', $request->tipo))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%'.$request->q.'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('documento', 'like', $term)
                        ->orWhere('referencia', 'like', $term)
                        ->orWhere('motivo', 'like', $term);
                });
            })
            ->orderByDesc('fecha')
            ->paginate(25);

        return view('inventario.movimientos.index', compact('movimientos', 'tipos'));
    }

    public function create(Request $request): View
    {
        $tipos = MovimientoInventario::TIPOS;
        $materiasPrimas = MateriaPrima::where('activo', true)->orderBy('nombre')->get();
        $productos = ProductoTerminado::where('activo', true)->orderBy('nombre')->get();

        // Modo edición por pre-selección de tipo
        $preseleccion = $request->get('tipo', 'compra_recepcion');

        return view('inventario.movimientos.create', compact('tipos', 'materiasPrimas', 'productos', 'preseleccion'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        try {
            $tipo = $data['tipo'];
            $origen = $this->resolverOrigen($data);
            $cantidad = $this->convertirCantidad($origen, $data['cantidad'], $tipo);

            if ($tipo === 'devolucion') {
                $this->service->registrarDevolucion(
                    $origen,
                    $cantidad,
                    $data['direccion_devolucion'],
                    $data['motivo'] ?? null,
                    $data['documento'] ?? null
                );
            } else {
                $this->service->registrar([
                    'tipo' => $tipo,
                    'origen' => $origen,
                    'cantidad' => $cantidad,
                    'documento' => $data['documento'] ?? null,
                    'referencia' => $data['referencia'] ?? null,
                    'motivo' => $data['motivo'] ?? null,
                    'costo_unitario' => $data['costo_unitario'] ?? null,
                    'costo_total' => $data['costo_total'] ?? null,
                    'fecha' => $data['fecha'] ?? now(),
                ]);
            }

            return redirect()->route('inventario.movimientos.index')
                ->with('success', 'Movimiento registrado correctamente.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function show(MovimientoInventario $movimiento): View
    {
        $movimiento->load(['user', 'origen', 'movimientoOriginal']);

        return view('inventario.movimientos.show', compact('movimiento'));
    }

    public function anularView(MovimientoInventario $movimiento): View
    {
        return view('inventario.movimientos.anular', compact('movimiento'));
    }

    public function anular(Request $request, MovimientoInventario $movimiento): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:255'],
        ]);

        try {
            $this->service->anular($movimiento, $data['motivo']);

            return redirect()->route('inventario.movimientos.show', $movimiento)
                ->with('success', 'Movimiento anulado y stock revertido.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    private function resolverOrigen(array $data)
    {
        if (! empty($data['materia_prima_id'])) {
            return MateriaPrima::findOrFail($data['materia_prima_id']);
        }

        if (! empty($data['producto_terminado_id'])) {
            return ProductoTerminado::findOrFail($data['producto_terminado_id']);
        }

        throw new \RuntimeException('Debe seleccionar una materia prima o un producto terminado.');
    }

    /**
     * Convierte la cantidad ingresada a la unidad interna:
     * gramos para materia prima, unidades para producto terminado.
     */
    private function convertirCantidad(mixed $origen, mixed $cantidad, string $tipo): int
    {
        $cantidad = (float) $cantidad;

        if ($origen instanceof MateriaPrima) {
            // Para materias primas se ingresa en la unidad_base (kg o g) → convertir a gramos.
            if ($origen->unidad_base === 'kg') {
                return (int) round($cantidad * 1000);
            }

            return (int) round($cantidad);
        }

        // Producto terminado: unidades.
        return (int) $cantidad;
    }

    private function validar(Request $request): array
    {
        return $request->validate([
            'tipo' => ['required', 'in:'.implode(',', array_keys(MovimientoInventario::TIPOS))],
            'producto_terminado_id' => ['nullable', 'exists:producto_terminados,id'],
            'materia_prima_id' => ['nullable', 'exists:materias_primas,id'],
            'cantidad' => ['required', 'numeric', 'gt:0'],
            'documento' => ['nullable', 'string', 'max:50'],
            'referencia' => ['nullable', 'string', 'max:120'],
            'motivo' => ['nullable', 'string', 'max:500'],
            'costo_unitario' => ['nullable', 'numeric', 'min:0'],
            'costo_total' => ['nullable', 'numeric', 'min:0'],
            'fecha' => ['nullable', 'date'],
            'direccion_devolucion' => ['nullable', 'in:entrada,salida'],
        ]);
    }
}

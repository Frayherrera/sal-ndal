<?php

namespace App\Http\Controllers;

use App\Models\ConteoFisico;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Services\ConteoFisicoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConteoFisicoController extends Controller
{
    public function __construct(protected ConteoFisicoService $service) {}

    public function index(Request $request): View
    {
        $conteos = ConteoFisico::with('user')->withCount('detalles')
            ->when($request->filled('estado'), fn ($q) => $q->where('estado', $request->estado))
            ->orderByDesc('created_at')
            ->paginate(20);

        $estados = ConteoFisico::ESTADOS;

        return view('inventario.conteo-fisico.index', compact('conteos', 'estados'));
    }

    public function create(Request $request): View
    {
        $tipo = $request->get('tipo', 'materia_prima');
        $materiasPrimas = MateriaPrima::where('activo', true)->with('inventario')->orderBy('nombre')->get();
        $productos = ProductoTerminado::where('activo', true)->with('inventario')->orderBy('nombre')->get();

        return view('inventario.conteo-fisico.create', compact('tipo', 'materiasPrimas', 'productos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'tipo' => ['required', 'in:materia_prima,producto_terminado'],
            'fecha_conteo' => ['required', 'date'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $conteo = $this->service->crear($data);

        return redirect()->route('inventario.conteo-fisico.show', $conteo)
            ->with('success', 'Conteo físico creado. Registra los detalles.');
    }

    public function show(ConteoFisico $conteo): View
    {
        $conteo->load(['detalles.materiaPrima', 'detalles.productoTerminado', 'user', 'aprobadoPor']);

        return view('inventario.conteo-fisico.show', compact('conteo'));
    }

    public function registrarDetalle(Request $request, ConteoFisico $conteo): RedirectResponse
    {
        try {
            $this->service->registrarDetalle($conteo, $this->prepararDetalles($request, $conteo->tipo));

            return back()->with('success', 'Líneas del conteo guardadas.');
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function completar(ConteoFisico $conteo): RedirectResponse
    {
        try {
            $this->service->completar($conteo);

            return back()->with('success', 'Conteo completado.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function aprobar(ConteoFisico $conteo): RedirectResponse
    {
        try {
            $this->service->aprobar($conteo);

            return back()->with('success', 'Conteo aprobado y ajustes generados.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    public function anular(Request $request, ConteoFisico $conteo): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $this->service->anular($conteo, $data['motivo'] ?? null);

            return back()->with('success', 'Conteo anulado.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }
    }

    /**
     * Normaliza las líneas del formulario para el servicio.
     *
     * - Ignora las filas totalmente vacías (sin artículo ni cantidad).
     * - La materia prima se digita en su `unidad_base` (kg o g) y se convierte a
     *   gramos; el producto terminado se digita en unidades.
     * - El stock del sistema no se toma del formulario: lo calcula el servicio.
     *
     * @return array<int, array<string, mixed>>
     */
    private function prepararDetalles(Request $request, string $tipo): array
    {
        $detalles = $request->input('detalles', []);

        if (! is_array($detalles)) {
            throw new \RuntimeException('Debes registrar al menos una línea.');
        }

        $esMateria = $tipo === 'materia_prima';
        $campo = $esMateria ? 'materia_prima_id' : 'producto_terminado_id';

        $materias = $esMateria
            ? MateriaPrima::whereIn('id', collect($detalles)->pluck($campo)->filter()->all())->get()->keyBy('id')
            : collect();

        $result = [];

        foreach ($detalles as $d) {
            if (! is_array($d)) {
                continue;
            }

            $id = $d[$campo] ?? null;
            $cantidad = $d['cantidad_fisica'] ?? null;

            if (blank($id) && blank($cantidad)) {
                continue;
            }

            if (is_numeric($cantidad) && $esMateria && ($materia = $materias->get((int) $id))) {
                $cantidad = $materia->unidad_base === 'kg'
                    ? (int) round((float) $cantidad * 1000)
                    : (int) round((float) $cantidad);
            }

            $result[] = [
                $campo => $id,
                'cantidad_fisica' => $cantidad,
                'motivo' => $d['motivo'] ?? null,
            ];
        }

        return $result;
    }
}

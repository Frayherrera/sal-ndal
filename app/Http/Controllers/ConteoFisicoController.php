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
        $detalles = $this->validarDetalles($request, $conteo->tipo);

        try {
            $this->service->registrarDetalle($conteo, $detalles);

            return back()->with('success', 'Detalles guardados.');
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
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

    private function validarDetalles(Request $request, string $tipo): array
    {
        $detalles = $request->input('detalles', []);

        if (! is_array($detalles) || count($detalles) === 0) {
            throw new \RuntimeException('Debe registrar al menos un detalle.');
        }

        $result = [];
        foreach ($detalles as $d) {
            $result[] = [
                'materia_prima_id' => $tipo === 'materia_prima' ? ($d['materia_prima_id'] ?? null) : null,
                'producto_terminado_id' => $tipo === 'producto_terminado' ? ($d['producto_terminado_id'] ?? null) : null,
                'stock_sistema' => (int) ($d['stock_sistema'] ?? 0),
                'cantidad_fisica' => (int) ($d['cantidad_fisica'] ?? 0),
                'motivo' => $d['motivo'] ?? null,
            ];
        }

        return $result;
    }
}

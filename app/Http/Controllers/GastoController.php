<?php

namespace App\Http\Controllers;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Services\GastoService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GastoController extends Controller
{
    public function __construct(protected GastoService $service) {}

    public function index(Request $request): View
    {
        $gastos = $this->consultaFiltrada($request)
            ->with(['categoria', 'user'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $registrados = $this->consultaFiltrada($request)->where('estado', Gasto::ESTADO_REGISTRADO);

        return view('gastos.index', [
            'gastos' => $gastos,
            'categorias' => CategoriaGasto::orderBy('nombre')->get(),
            'resumen' => [
                'total' => round((float) (clone $registrados)->sum('monto'), 2),
                'count' => (clone $registrados)->count(),
                'automaticos' => round((float) (clone $registrados)->whereNotNull('movimiento_inventario_id')->sum('monto'), 2),
            ],
        ]);
    }

    public function create(): View
    {
        return view('gastos.create', [
            'gasto' => new Gasto(['fecha' => now()]),
            'categorias' => CategoriaGasto::where('activo', true)->whereNull('clave')->orderBy('nombre')->get(),
            'metodos' => Gasto::METODOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        try {
            $gasto = $this->service->crear($this->validar($request));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.index')
            ->with('success', "Gasto {$gasto->numero} registrado correctamente.");
    }

    public function edit(Gasto $gasto): View|RedirectResponse
    {
        if ($gasto->esAutomatico() || $gasto->estaAnulado()) {
            return redirect()->route('gastos.index')
                ->with('error', $gasto->estaAnulado()
                    ? 'El gasto está anulado y no se puede editar.'
                    : 'Este gasto se generó desde una compra de inventario y no se edita aquí.');
        }

        return view('gastos.edit', [
            'gasto' => $gasto,
            'categorias' => CategoriaGasto::whereNull('clave')
                ->where(fn (Builder $q) => $q->where('activo', true)->orWhere('id', $gasto->categoria_gasto_id))
                ->orderBy('nombre')
                ->get(),
            'metodos' => Gasto::METODOS,
        ]);
    }

    public function update(Request $request, Gasto $gasto): RedirectResponse
    {
        try {
            $this->service->actualizar($gasto, $this->validar($request));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.index')
            ->with('success', "Gasto {$gasto->numero} actualizado.");
    }

    public function anularView(Gasto $gasto): View
    {
        $gasto->load(['categoria', 'movimiento']);

        return view('gastos.anular', compact('gasto'));
    }

    public function anular(Request $request, Gasto $gasto): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ], [
            'motivo.required' => 'Debes indicar el motivo de la anulación.',
        ]);

        try {
            $this->service->anular($gasto, $data['motivo']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('gastos.index')
            ->with('success', "Gasto {$gasto->numero} anulado.");
    }

    public function export(Request $request): StreamedResponse
    {
        $gastos = $this->consultaFiltrada($request)
            ->with(['categoria', 'user'])
            ->orderBy('fecha')
            ->orderBy('id')
            ->lazy();

        return response()->streamDownload(function () use ($gastos) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Número', 'Fecha', 'Categoría', 'Descripción', 'Proveedor', 'Método de pago',
                'Valor', 'Estado', 'Origen', 'Registrado por',
            ], ';');

            foreach ($gastos as $gasto) {
                fputcsv($out, [
                    $gasto->numero,
                    $gasto->fecha->format('d/m/Y'),
                    $gasto->categoria?->nombre,
                    $gasto->descripcion,
                    $gasto->proveedor,
                    $gasto->metodo_pago ? $gasto->metodoPago() : '',
                    number_format((float) $gasto->monto, 2, ',', ''),
                    Gasto::ESTADOS[$gasto->estado] ?? $gasto->estado,
                    $gasto->esAutomatico() ? 'Compra de inventario' : 'Manual',
                    $gasto->user?->name,
                ], ';');
            }

            fclose($out);
        }, 'gastos-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function consultaFiltrada(Request $request): Builder
    {
        return Gasto::query()
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('fecha', '<=', $request->hasta))
            ->when($request->filled('categoria_id'), fn (Builder $q) => $q->where('categoria_gasto_id', $request->categoria_id))
            ->when($request->filled('estado'), fn (Builder $q) => $q->where('estado', $request->estado))
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $term = '%'.$request->q.'%';

                $q->where(fn (Builder $sub) => $sub
                    ->where('numero', 'like', $term)
                    ->orWhere('descripcion', 'like', $term)
                    ->orWhere('proveedor', 'like', $term));
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'categoria_gasto_id' => ['required', 'exists:categorias_gasto,id'],
            'descripcion' => ['required', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'gt:0', 'max:999999999999'],
            'metodo_pago' => ['nullable', 'in:'.implode(',', array_keys(Gasto::METODOS))],
            'proveedor' => ['nullable', 'string', 'max:120'],
        ], [
            'fecha.required' => 'La fecha del gasto es obligatoria.',
            'fecha.before_or_equal' => 'La fecha del gasto no puede ser futura.',
            'categoria_gasto_id.required' => 'Selecciona una categoría.',
            'categoria_gasto_id.exists' => 'La categoría seleccionada no existe.',
            'descripcion.required' => 'Describe el gasto.',
            'monto.required' => 'Indica el valor del gasto.',
            'monto.gt' => 'El valor del gasto debe ser mayor a cero.',
        ]);
    }
}

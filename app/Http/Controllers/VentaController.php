<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\PagoVenta;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VentaController extends Controller
{
    public function __construct(protected VentaService $service) {}

    public function index(Request $request): View
    {
        $ventas = $this->consultaFiltrada($request)
            ->with(['cliente', 'user'])
            ->withSum('pagos', 'monto')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('ventas.index', [
            'ventas' => $ventas,
            'clientes' => Cliente::orderBy('nombre')->get(),
            'resumen' => $this->resumenFiltrado($request),
            'estados' => Venta::ESTADO_PAGO_LABELS,
        ]);
    }

    public function create(): View
    {
        return view('ventas.create', [
            'clientes' => Cliente::orderBy('nombre')->get(),
            'metodos' => PagoVenta::METODOS,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validar($request);

        try {
            $venta = $this->service->crear($data);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('ventas.show', $venta)
            ->with('success', "Venta {$venta->numero} registrada correctamente.");
    }

    public function show(Venta $venta): View
    {
        $venta->load(['cliente', 'user', 'anuladaPor', 'detalles.producto', 'pagos.user'])
            ->loadSum('pagos', 'monto');

        return view('ventas.show', compact('venta'));
    }

    public function recibo(Venta $venta): View
    {
        $venta->load(['cliente', 'user', 'detalles.producto', 'pagos'])
            ->loadSum('pagos', 'monto');

        return view('ventas.recibo', compact('venta'));
    }

    public function anularView(Venta $venta): View
    {
        $venta->load('cliente')->loadSum('pagos', 'monto');

        return view('ventas.anular', [
            'venta' => $venta,
            'tienePagos' => $venta->pagos()->exists(),
        ]);
    }

    public function anular(Request $request, Venta $venta): RedirectResponse
    {
        $data = $request->validate([
            'motivo' => ['required', 'string', 'max:500'],
        ], [
            'motivo.required' => 'Debes indicar el motivo de la anulación.',
        ]);

        try {
            $this->service->anular($venta, $data['motivo']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('ventas.show', $venta)
            ->with('success', 'Venta anulada correctamente.');
    }

    public function export(Request $request): StreamedResponse
    {
        $ventas = $this->consultaFiltrada($request)
            ->with(['cliente', 'user'])
            ->withSum('pagos', 'monto')
            ->orderBy('fecha')
            ->orderBy('id')
            ->lazy();

        return response()->streamDownload(function () use ($ventas) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Número', 'Fecha', 'Cliente', 'Local', 'Ciudad', 'Forma de pago', 'Estado',
                'Total', 'Pagado', 'Saldo', 'Vendedor', 'Observaciones',
            ], ';');

            foreach ($ventas as $venta) {
                fputcsv($out, [
                    $venta->numero,
                    $venta->fecha->format('d/m/Y'),
                    $venta->cliente?->nombre,
                    $venta->cliente?->local,
                    $venta->cliente?->ciudad,
                    $venta->formaPago(),
                    Venta::ESTADO_PAGO_LABELS[$venta->estadoPago()] ?? $venta->estadoPago(),
                    number_format((float) $venta->total, 2, ',', ''),
                    number_format($venta->totalPagado(), 2, ',', ''),
                    number_format($venta->saldo(), 2, ',', ''),
                    $venta->user?->name,
                    $venta->observaciones,
                ], ';');
            }

            fclose($out);
        }, 'ventas-'.now()->format('Y-m-d').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function consultaFiltrada(Request $request): Builder
    {
        return Venta::query()
            ->when($request->filled('desde'), fn (Builder $q) => $q->whereDate('fecha', '>=', $request->desde))
            ->when($request->filled('hasta'), fn (Builder $q) => $q->whereDate('fecha', '<=', $request->hasta))
            ->when($request->filled('cliente_id'), fn (Builder $q) => $q->where('cliente_id', $request->cliente_id))
            ->when($request->filled('q'), function (Builder $q) use ($request) {
                $term = '%'.$request->q.'%';

                $q->where(function (Builder $sub) use ($term) {
                    $sub->where('numero', 'like', $term)
                        ->orWhereHas('cliente', fn (Builder $cliente) => $cliente
                            ->where('nombre', 'like', $term)
                            ->orWhere('local', 'like', $term));
                });
            })
            ->when($request->filled('estado'), fn (Builder $q) => $this->aplicarFiltroEstado($q, (string) $request->estado));
    }

    private function aplicarFiltroEstado(Builder $query, string $estado): Builder
    {
        if ($estado === 'anulada') {
            return $query->where('estado', Venta::ESTADO_ANULADA);
        }

        $query->where('estado', Venta::ESTADO_CONFIRMADA);

        $saldoPendiente = 'ventas.total > (select coalesce(sum(pv.monto), 0) from pago_venta pv where pv.venta_id = ventas.id)';

        return match ($estado) {
            'pagada' => $query->whereRaw('ventas.total <= (select coalesce(sum(pv.monto), 0) from pago_venta pv where pv.venta_id = ventas.id)'),
            'pendiente' => $query->whereRaw($saldoPendiente),
            'vencida' => $query->whereRaw($saldoPendiente)
                ->whereNotNull('fecha_vencimiento')
                ->whereDate('fecha_vencimiento', '<', now()->toDateString()),
            default => $query,
        };
    }

    /**
     * @return array{total: float, pagado: float, saldo: float, count: int}
     */
    private function resumenFiltrado(Request $request): array
    {
        $base = $this->consultaFiltrada($request)->where('estado', Venta::ESTADO_CONFIRMADA);

        $total = round((float) (clone $base)->sum('total'), 2);
        $pagado = round((float) PagoVenta::whereIn('venta_id', (clone $base)->select('ventas.id'))->sum('monto'), 2);

        return [
            'total' => $total,
            'pagado' => $pagado,
            'saldo' => round(max($total - $pagado, 0), 2),
            'count' => (clone $base)->count(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'cliente_id' => ['required', 'exists:clientes,id'],
            'fecha' => ['required', 'date', 'before_or_equal:today'],
            'fecha_vencimiento' => ['nullable', 'date', 'after_or_equal:fecha'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'abono_inicial' => ['nullable', 'numeric', 'min:0'],
            'metodo_pago' => ['nullable', 'in:'.implode(',', array_keys(PagoVenta::METODOS))],
            'referencia_pago' => ['nullable', 'string', 'max:100'],
            'total' => ['required', 'numeric', 'gt:0', 'max:9999999999'],
            'forma_pago' => ['required', 'in:contado,credito'],
        ], [
            'cliente_id.required' => 'Debes seleccionar un cliente.',
            'cliente_id.exists' => 'El cliente seleccionado no existe.',
            'fecha.required' => 'La fecha de la venta es obligatoria.',
            'fecha.before_or_equal' => 'La fecha de la venta no puede ser futura.',
            'fecha_vencimiento.after_or_equal' => 'El vencimiento no puede ser anterior a la fecha de la venta.',
            'total.required' => 'Indica el valor total de la venta.',
            'total.numeric' => 'El valor total debe ser un número.',
            'total.gt' => 'El valor total de la venta debe ser mayor a cero.',
        ]);
    }
}

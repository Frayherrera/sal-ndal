<?php

namespace App\Http\Controllers;

use App\Models\ProductoTerminado;
use App\Models\Ruta;
use App\Models\ViajeCamion;
use App\Services\ViajeCamionService;
use App\Support\Paquetes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ViajeCamionController extends Controller
{
    public function __construct(protected ViajeCamionService $service) {}

    public function index(): View
    {
        $enRuta = $this->service->viajeEnRuta()?->load('ruta', 'detalles');
        $stock = $this->service->stockCamion();

        return view('camion.index', [
            'enRuta' => $enRuta,
            'unidadesEnCamion' => array_sum($stock),
            'productosEnCamion' => count($stock),
            'rutaSugerida' => $enRuta ? null : $this->service->rutaSugerida(),
            'hayRutas' => Ruta::where('activo', true)->exists(),
            'viajes' => ViajeCamion::with('ruta')->withSum('detalles', 'vendido')->withSum('detalles', 'cargado')
                ->orderByDesc('fecha_salida')->orderByDesc('id')->paginate(15),
        ]);
    }

    public function create(Request $request): View|RedirectResponse
    {
        if ($viaje = $this->service->viajeEnRuta()) {
            return redirect()->route('camion.show', $viaje)
                ->with('error', "El viaje {$viaje->numero} sigue en ruta. Ciérralo antes de cargar el siguiente.");
        }

        $rutas = Ruta::where('activo', true)->orderBy('orden')->orderBy('nombre')->get();

        if ($rutas->isEmpty()) {
            return redirect()->route('camion.rutas.create')->with('error', 'Primero crea las rutas del camión.');
        }

        $ruta = $rutas->firstWhere('id', (int) $request->query('ruta_id')) ?? $this->service->rutaSugerida() ?? $rutas->first();
        $enCamion = $this->service->stockCamion();
        $promedios = $this->service->promedioVendido($ruta);

        $productos = ProductoTerminado::with('inventario')
            ->where(fn ($q) => $q->where('activo', true)->orWhereIn('id', array_keys($enCamion)))
            ->orderBy('nombre')
            ->get()
            ->map(fn (ProductoTerminado $p) => [
                'producto' => $p,
                'en_camion' => (int) ($enCamion[$p->id] ?? 0),
                'bodega' => $p->stock_disponible(),
                'promedio' => (int) ($promedios[$p->id] ?? 0),
            ]);

        return view('camion.create', compact('rutas', 'ruta', 'productos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'ruta_id' => ['required', 'exists:rutas,id'],
            'fecha_salida' => ['required', 'date', 'before_or_equal:today'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
            'lineas' => ['nullable', 'array'],
            'lineas.*.surtido_p' => ['nullable', 'integer', 'min:0'],
            'lineas.*.surtido_u' => ['nullable', 'integer', 'min:0'],
            'lineas.*.bajado_p' => ['nullable', 'integer', 'min:0'],
            'lineas.*.bajado_u' => ['nullable', 'integer', 'min:0'],
        ], [
            'ruta_id.required' => 'Selecciona la ruta del viaje.',
            'fecha_salida.before_or_equal' => 'La fecha de salida no puede ser futura.',
            'lineas.*.*.integer' => 'Las cantidades deben ser números enteros.',
            'lineas.*.*.min' => 'Las cantidades no pueden ser negativas.',
        ]);

        $lineas = collect($data['lineas'] ?? [])->map(fn (array $l) => [
            'surtido' => Paquetes::aUnidades($l['surtido_p'] ?? 0, $l['surtido_u'] ?? 0),
            'bajado' => Paquetes::aUnidades($l['bajado_p'] ?? 0, $l['bajado_u'] ?? 0),
        ])->all();

        try {
            $viaje = $this->service->cargar([
                'ruta_id' => (int) $data['ruta_id'],
                'fecha_salida' => $data['fecha_salida'],
                'observaciones' => $data['observaciones'] ?? null,
                'lineas' => $lineas,
            ]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('camion.show', $viaje)
            ->with('success', "Viaje {$viaje->numero} cargado: el camión sale a {$viaje->ruta->nombre}.");
    }

    public function show(ViajeCamion $viaje): View
    {
        $viaje->load(['ruta', 'user', 'detalles.producto']);

        return view('camion.show', [
            'viaje' => $viaje,
            'detalles' => $viaje->detalles->sortBy(fn ($d) => $d->producto?->nombre)->values(),
            'resultado' => $viaje->estaCerrado() ? $this->service->resultado($viaje) : null,
        ]);
    }

    public function cerrarView(ViajeCamion $viaje): View|RedirectResponse
    {
        if (! $viaje->estaEnRuta()) {
            return redirect()->route('camion.show', $viaje)->with('error', 'Este viaje ya no está en ruta.');
        }

        $viaje->load(['ruta', 'detalles.producto']);

        return view('camion.cerrar', [
            'viaje' => $viaje,
            'detalles' => $viaje->detalles->sortBy(fn ($d) => $d->producto?->nombre)->values(),
        ]);
    }

    public function cerrar(Request $request, ViajeCamion $viaje): RedirectResponse
    {
        $data = $request->validate([
            'fecha_regreso' => ['required', 'date', 'before_or_equal:today'],
            'lineas' => ['nullable', 'array'],
            'lineas.*.*' => ['nullable', 'integer', 'min:0'],
        ], [
            'fecha_regreso.before_or_equal' => 'La fecha de regreso no puede ser futura.',
            'lineas.*.*.integer' => 'Las cantidades deben ser números enteros.',
            'lineas.*.*.min' => 'Las cantidades no pueden ser negativas.',
        ]);

        $lineas = collect($data['lineas'] ?? [])->map(fn (array $l) => [
            'regresado' => Paquetes::aUnidades($l['regresado_p'] ?? 0, $l['regresado_u'] ?? 0),
            'danado' => Paquetes::aUnidades($l['danado_p'] ?? 0, $l['danado_u'] ?? 0),
            'regalado' => Paquetes::aUnidades($l['regalado_p'] ?? 0, $l['regalado_u'] ?? 0),
        ])->all();

        try {
            $this->service->cerrar($viaje, ['fecha_regreso' => $data['fecha_regreso'], 'lineas' => $lineas]);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('camion.show', $viaje)
            ->with('success', "Viaje {$viaje->numero} cerrado. Ya puedes cargar el siguiente.");
    }

    public function anular(Request $request, ViajeCamion $viaje): RedirectResponse
    {
        $data = $request->validate(['motivo' => ['required', 'string', 'max:500']], [
            'motivo.required' => 'Indica el motivo de la anulación.',
        ]);

        try {
            $this->service->anular($viaje, $data['motivo']);
        } catch (RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('camion.show', $viaje)
            ->with('success', "Viaje {$viaje->numero} anulado: el surtido volvió a bodega.");
    }
}

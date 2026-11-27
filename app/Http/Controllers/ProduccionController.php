<?php

namespace App\Http\Controllers;

use App\Models\ProductoTerminado;
use App\Services\MovimientoInventarioService;
use App\Support\Paquetes;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class ProduccionController extends Controller
{
    public function __construct(protected MovimientoInventarioService $service) {}

    public function create(): View
    {
        $productos = ProductoTerminado::where('activo', true)
            ->with('receta')
            ->ordenado()
            ->get();

        return view('inventario.produccion.create', compact('productos'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'producto_terminado_id' => ['required', 'exists:producto_terminados,id'],
            'paquetes' => ['nullable', 'integer', 'min:0'],
            'sueltas' => ['nullable', 'integer', 'min:0'],
            'documento' => ['nullable', 'string', 'max:50'],
            'motivo' => ['nullable', 'string', 'max:500'],
            'fecha' => ['nullable', 'date'],
        ]);

        // Se captura en paquetes + unidades sueltas; el inventario se guarda en unidades
        $unidades = Paquetes::aUnidades($data['paquetes'] ?? 0, $data['sueltas'] ?? 0);

        if ($unidades <= 0) {
            return back()->withInput()->withErrors(['paquetes' => 'Indica cuántos paquetes o unidades vas a producir.']);
        }

        try {
            $producto = ProductoTerminado::findOrFail($data['producto_terminado_id']);
            $movimientos = $this->service->producir(
                $producto,
                $unidades,
                [
                    'documento' => $data['documento'] ?? null,
                    'motivo' => $data['motivo'] ?? null,
                    'fecha' => $data['fecha'] ?? now(),
                ]
            );

            return redirect()->route('inventario.movimientos.index')
                ->with('success', 'Producción registrada: '.Paquetes::texto($unidades)." de \"{$producto->nombre}\". Materias primas descontadas.")
                ->with('movimientosProduccion', count($movimientos));
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }
}

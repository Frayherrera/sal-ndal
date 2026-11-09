<?php

namespace App\Http\Controllers;

use App\Models\PagoVenta;
use App\Models\Venta;
use App\Services\PagoVentaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class PagoVentaController extends Controller
{
    public function __construct(protected PagoVentaService $service) {}

    public function create(Venta $venta): View
    {
        $venta->load('cliente')->loadSum('pagos', 'monto');

        return view('ventas.abonos.create', [
            'venta' => $venta,
            'metodos' => PagoVenta::METODOS,
        ]);
    }

    public function store(Request $request, Venta $venta): RedirectResponse
    {
        $data = $request->validate([
            'monto' => ['required', 'numeric', 'min:0.01'],
            'metodo' => ['required', 'in:'.implode(',', array_keys(PagoVenta::METODOS))],
            'referencia' => ['nullable', 'string', 'max:100'],
            'nota' => ['nullable', 'string', 'max:500'],
            'fecha' => ['nullable', 'date'],
        ], [
            'monto.required' => 'El monto del abono es obligatorio.',
            'monto.min' => 'El monto del abono debe ser mayor a cero.',
            'metodo.required' => 'Selecciona el método de pago.',
        ]);

        try {
            $this->service->registrar($venta, $data);
        } catch (RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('ventas.show', $venta)
            ->with('success', 'Abono registrado correctamente.');
    }
}

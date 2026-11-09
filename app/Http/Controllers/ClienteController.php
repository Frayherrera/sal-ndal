<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Services\ClienteService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClienteController extends Controller
{
    public function __construct(
        protected ClienteService $service,
    ) {}

    public function index(): View
    {
        return view('clientes.index', [
            'clientes' => Cliente::orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('clientes.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $this->service->crear($this->validar($request));

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente registrado correctamente.');
    }

    public function edit(Cliente $cliente): View
    {
        return view('clientes.edit', compact('cliente'));
    }

    public function update(Request $request, Cliente $cliente): RedirectResponse
    {
        $this->service->actualizar($cliente, $this->validar($request));

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente actualizado correctamente.');
    }

    public function destroy(Cliente $cliente): RedirectResponse
    {
        $this->service->eliminar($cliente);

        return redirect()->route('clientes.index')
            ->with('success', 'Cliente eliminado.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:120'],
            'local' => ['nullable', 'string', 'max:120'],
            'ciudad' => ['nullable', 'string', 'max:120'],
            'telefono' => ['nullable', 'string', 'max:30'],
        ], [
            'nombre.required' => 'El nombre es obligatorio.',
            'nombre.max' => 'El nombre no puede superar los 120 caracteres.',
            'local.max' => 'El local no puede superar los 120 caracteres.',
            'ciudad.max' => 'La ciudad no puede superar los 120 caracteres.',
            'telefono.max' => 'El teléfono no puede superar los 30 caracteres.',
        ]);
    }
}

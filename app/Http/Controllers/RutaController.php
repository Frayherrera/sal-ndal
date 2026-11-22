<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Ruta;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class RutaController extends Controller
{
    public function index(): View
    {
        return view('camion.rutas.index', [
            'rutas' => Ruta::withCount('viajes')->orderBy('orden')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('camion.rutas.create', [
            'ruta' => new Ruta(['orden' => (int) Ruta::max('orden') + 1, 'activo' => true]),
            'ciudades' => $this->ciudadesClientes(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Ruta::create($this->validar($request));

        return redirect()->route('camion.rutas.index')->with('success', 'Ruta creada.');
    }

    public function edit(Ruta $ruta): View
    {
        return view('camion.rutas.edit', [
            'ruta' => $ruta,
            'ciudades' => $this->ciudadesClientes(),
        ]);
    }

    public function update(Request $request, Ruta $ruta): RedirectResponse
    {
        $ruta->update($this->validar($request, $ruta));

        return redirect()->route('camion.rutas.index')->with('success', 'Ruta actualizada.');
    }

    public function destroy(Ruta $ruta): RedirectResponse
    {
        if ($ruta->viajes()->exists()) {
            return back()->with('error', "La ruta \"{$ruta->nombre}\" tiene viajes registrados. Desactívala en lugar de eliminarla.");
        }

        $ruta->delete();

        return redirect()->route('camion.rutas.index')->with('success', 'Ruta eliminada.');
    }

    /**
     * Ciudades distintas de los clientes, para sugerirlas en el formulario.
     *
     * @return array<int, string>
     */
    private function ciudadesClientes(): array
    {
        return Cliente::whereNotNull('ciudad')->where('ciudad', '!=', '')
            ->orderBy('ciudad')->distinct()->pluck('ciudad')->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function validar(Request $request, ?Ruta $ruta = null): array
    {
        $data = $request->validate([
            'nombre' => ['required', 'string', 'max:80', Rule::unique('rutas', 'nombre')->ignore($ruta?->id)],
            'orden' => ['required', 'integer', 'min:1', 'max:999'],
            'ciudades' => ['nullable', 'array'],
            'ciudades.*' => ['string', 'max:120'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ], [
            'nombre.required' => 'El nombre de la ruta es obligatorio.',
            'nombre.unique' => 'Ya existe una ruta con ese nombre.',
            'orden.required' => 'Indica la posición de la ruta en la rotación.',
        ]);

        $data['ciudades'] = array_values(array_unique(array_filter(array_map('trim', $data['ciudades'] ?? []))));
        $data['activo'] = $request->boolean('activo');

        return $data;
    }
}

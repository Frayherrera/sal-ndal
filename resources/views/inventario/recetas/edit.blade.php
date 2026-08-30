@extends('layouts.app')

@section('title', 'Editar Receta: ' . $producto->nombre)

@section('content')
    <div class="max-w-3xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.recetas.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Receta: {{ $producto->nombre }}</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <div class="flex items-center gap-2 mb-6">
                <span class="font-mono text-blue-300 text-sm">{{ $producto->codigo }}</span>
                <span class="text-white/40 text-sm">· Peso neto {{ $producto->peso_neto }} g</span>
            </div>

            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6">
                    <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('inventario.recetas.update', $producto) }}">
                @csrf
                @method('PUT')

                <div class="mb-6">
                    <label class="block text-sm font-medium text-white/80 mb-3">Materias primas y cantidad por unidad *</label>
                    <p class="text-white/40 text-xs mb-4">
                        Gramos de cada materia prima necesarios para producir UNA unidad.
                    </p>
                    <div id="lineas" class="space-y-3">
                        @foreach ($producto->receta as $i => $linea)
                            <div class="linea-receta grid grid-cols-[1fr_auto_auto_auto] gap-3 items-center">
                                <select name="lineas[{{ $i }}][materia_prima_id]" class="input-glass bg-gray-900/60" required>
                                    @foreach ($materiasPrimas as $mp)
                                        <option value="{{ $mp->id }}" @selected(old("lineas.$i.materia_prima_id", $linea->materia_prima_id) == $mp->id)>
                                            {{ $mp->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="number" name="lineas[{{ $i }}][gramos_por_unidad]" min="0.001" step="0.001"
                                       value="{{ old("lineas.$i.gramos_por_unidad", $linea->gramos_por_unidad) }}"
                                       placeholder="Gramos" class="input-glass w-32" required>
                                <span class="text-white/50 text-sm">g</span>
                                <button type="button" class="quitar-linea w-9 h-9 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
                                    <i class="fas fa-trash text-xs"></i>
                                </button>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" id="agregarLinea"
                            class="mt-3 flex items-center gap-2 text-blue-300 hover:text-blue-200 text-sm font-medium">
                        <i class="fas fa-plus"></i> Agregar materia prima
                    </button>
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <a href="{{ route('inventario.recetas.index') }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const lineas = document.getElementById('lineas');
    const agregar = document.getElementById('agregarLinea');
    if (!lineas || !agregar) return;

    let indice = {{ max($producto->receta->count(), 1) }};
    const opcionesMateria = lineas.querySelector('.linea-receta select').innerHTML;

    function nuevaLinea() {
        const div = document.createElement('div');
        div.className = 'linea-receta grid grid-cols-[1fr_auto_auto_auto] gap-3 items-center';
        div.innerHTML = `
            <select name="lineas[${indice}][materia_prima_id]" class="input-glass bg-gray-900/60" required>
                ${opcionesMateria}
            </select>
            <input type="number" name="lineas[${indice}][gramos_por_unidad]" min="0.001" step="0.001"
                   placeholder="Gramos" class="input-glass w-32" required>
            <span class="text-white/50 text-sm">g</span>
            <button type="button" class="quitar-linea w-9 h-9 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
                <i class="fas fa-trash text-xs"></i>
            </button>`;
        lineas.appendChild(div);
        indice++;
    }

    lineas.addEventListener('click', function (e) {
        if (e.target.closest('.quitar-linea')) {
            e.target.closest('.linea-receta').remove();
        }
    });

    agregar.addEventListener('click', nuevaLinea);
})();
</script>
@endpush

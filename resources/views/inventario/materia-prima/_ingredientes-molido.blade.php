@php
    $lineasIniciales = $lineasIniciales ?? [];
    if (empty($lineasIniciales)) {
        $lineasIniciales = [['ingrediente_id' => '', 'gramos_por_kg' => '']];
    }
    $esMolido = (bool) old('es_molido', $molidoEsMolido ?? false);
@endphp

<div class="mt-6">
    <label class="flex items-center gap-3 cursor-pointer">
        <input type="checkbox" name="es_molido" id="esMolido" value="1" @checked($esMolido)
               class="w-5 h-5 rounded border-white/30 bg-white/10 text-blue-500 focus:ring-blue-500">
        <span class="text-white/80 text-sm">Es una materia prima molida (se produce a partir de otros ingredientes)</span>
    </label>
</div>

<div id="ingredientesMolido" class="mt-6 {{ $esMolido ? '' : 'hidden' }}">
    <label class="block text-sm font-medium text-white/80 mb-3">Ingredientes *</label>
    <p class="text-white/40 text-xs mb-4">
        Indica cuántos gramos de cada materia prima se usan para producir 1 kg de este molido.
    </p>
    <div id="lineasMolido" class="space-y-3">
        @foreach ($lineasIniciales as $i => $linea)
            <div class="linea-molido grid grid-cols-[1fr_auto_auto_auto] gap-3 items-center">
                <select name="lineas[{{ $i }}][ingrediente_id]" class="input-glass bg-gray-900/60">
                    <option value="">Materia prima...</option>
                    @foreach ($ingredientes as $ing)
                        <option value="{{ $ing->id }}" @selected(($linea['ingrediente_id'] ?? null) == $ing->id)>
                            {{ $ing->nombre }}
                        </option>
                    @endforeach
                </select>
                <input type="number" name="lineas[{{ $i }}][gramos_por_kg]" min="0.001" step="0.001"
                       value="{{ $linea['gramos_por_kg'] ?? '' }}" placeholder="g/kg" class="input-glass w-32">
                <span class="text-white/50 text-sm">g/kg</span>
                <button type="button" class="quitar-linea w-9 h-9 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
                    <i class="fas fa-trash text-xs"></i>
                </button>
            </div>
        @endforeach
    </div>
    <button type="button" id="agregarLineaMolido"
            class="mt-3 flex items-center gap-2 text-blue-300 hover:text-blue-200 text-sm font-medium">
        <i class="fas fa-plus"></i> Agregar ingrediente
    </button>
</div>

@push('scripts')
<script>
(function () {
    const checkbox = document.getElementById('esMolido');
    const contenedor = document.getElementById('ingredientesMolido');
    const lineas = document.getElementById('lineasMolido');
    const agregar = document.getElementById('agregarLineaMolido');
    if (!checkbox || !contenedor || !lineas || !agregar) return;

    function alternar() {
        contenedor.classList.toggle('hidden', !checkbox.checked);
    }

    checkbox.addEventListener('change', alternar);
    alternar();

    let indice = {{ $lineasIniciales ? max(array_keys($lineasIniciales)) + 1 : 1 }};
    const opciones = lineas.querySelector('.linea-molido select')?.innerHTML ?? '';

    function nuevaLinea() {
        const div = document.createElement('div');
        div.className = 'linea-molido grid grid-cols-[1fr_auto_auto_auto] gap-3 items-center';
        div.innerHTML = `
            <select name="lineas[${indice}][ingrediente_id]" class="input-glass bg-gray-900/60">
                ${opciones}
            </select>
            <input type="number" name="lineas[${indice}][gramos_por_kg]" min="0.001" step="0.001"
                   placeholder="g/kg" class="input-glass w-32">
            <span class="text-white/50 text-sm">g/kg</span>
            <button type="button" class="quitar-linea w-9 h-9 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
                <i class="fas fa-trash text-xs"></i>
            </button>`;
        lineas.appendChild(div);
        indice++;
    }

    lineas.addEventListener('click', function (e) {
        if (e.target.closest('.quitar-linea')) {
            e.target.closest('.linea-molido').remove();
        }
    });

    agregar.addEventListener('click', nuevaLinea);
})();
</script>
@endpush

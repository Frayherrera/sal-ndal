@extends('layouts.app')

@section('title', 'Registrar Producción')

@section('content')
    <div class="max-w-3xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.movimientos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Registrar Producción</h1>
        </div>

        @php
            $productosJson = $productos->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre' => $p->nombre,
                    'tiene_receta' => $p->receta->isNotEmpty(),
                    'lineas' => $p->receta->map(fn ($l) => [
                        'nombre' => $l->materiaPrima?->nombre ?? 'N/D',
                        'gramos' => (float) $l->gramos_por_unidad,
                    ])->values(),
                ];
            })->values();
        @endphp

        <div class="glass rounded-2xl p-8">
            @if ($errors->any())
                <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6">
                    <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form method="POST" action="{{ route('inventario.produccion.store') }}">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Producto terminado *</label>
                    <select name="producto_terminado_id" id="productoSelect" class="input-glass bg-gray-900/60">
                        <option value="">Seleccionar producto...</option>
                        @foreach ($productos as $producto)
                            <option value="{{ $producto->id }}" @selected(old('producto_terminado_id') == $producto->id)>
                                {{ $producto->nombre }} ({{ $producto->codigo }})
                                @if ($producto->receta->isEmpty()) — sin receta @endif
                            </option>
                        @endforeach
                    </select>
                    <p class="text-white/40 text-xs mt-1" id="recetaHint"></p>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Unidades a producir *</label>
                    <input type="number" min="1" step="1" name="cantidad" id="cantidadInput"
                           value="{{ old('cantidad') }}" required class="input-glass">
                </div>

                <!-- Desglose de materias primas por consumir -->
                <div class="mb-5 hidden" id="desglose">
                    <label class="block text-sm font-medium text-white/80 mb-3">Materias primas que se descontarán</label>
                    <div class="bg-gray-900/50 border border-white/10 rounded-xl p-4 space-y-2" id="desgloseLista"></div>
                </div>

                <div class="grid sm:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Documento</label>
                        <input type="text" name="documento" value="{{ old('documento') }}"
                               class="input-glass" placeholder="Opcional">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Fecha</label>
                        <input type="datetime-local" name="fecha" value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}"
                               class="input-glass bg-gray-900/60">
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Motivo / Nota</label>
                    <textarea name="motivo" rows="2" class="input-glass" placeholder="Opcional">{{ old('motivo') }}</textarea>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-industry"></i>
                        Producir
                    </button>
                    <a href="{{ route('inventario.movimientos.index') }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const productos = @json($productosJson);
    const select = document.getElementById('productoSelect');
    const cantidad = document.getElementById('cantidadInput');
    const desglose = document.getElementById('desglose');
    const lista = document.getElementById('desgloseLista');
    const hint = document.getElementById('recetaHint');

    function actualizar() {
        const producto = productos.find(p => p.id === Number(select.value));
        const unidades = Number(cantidad.value) || 0;

        if (!producto) {
            hint.textContent = '';
            desglose.classList.add('hidden');
            return;
        }

        if (!producto.tiene_receta) {
            hint.textContent = 'Este producto no tiene receta. Defínela en el módulo de Recetas antes de producir.';
            hint.className = 'text-xs mt-1 text-yellow-400';
            desglose.classList.add('hidden');
            return;
        }

        hint.textContent = 'Receta definida.';
        hint.className = 'text-xs mt-1 text-green-400';
        desglose.classList.remove('hidden');

        const filas = producto.lineas.map(linea => {
            const gramosTotal = (linea.gramos * unidades);
            const kg = (gramosTotal / 1000).toFixed(3);
            return `<div class="flex items-center justify-between text-sm">
                        <span class="text-white/70">${linea.nombre}</span>
                        <span class="text-white font-medium">${gramosTotal.toFixed(3)} g <span class="text-white/40 text-xs">(${kg} kg)</span></span>
                    </div>`;
        });

        lista.innerHTML = filas.join('');
    }

    select.addEventListener('change', actualizar);
    cantidad.addEventListener('input', actualizar);
    actualizar();
})();
</script>
@endpush

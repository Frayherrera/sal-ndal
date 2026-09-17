@extends('layouts.app')

@section('title', 'Producir Molido')

@section('content')
    <div class="max-w-3xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.materia-prima.show', $materia) }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Producir Molido</h1>
        </div>

        @php
            $ingredientesJson = $materia->detalleMolido->map(fn ($l) => [
                'nombre' => $l->ingrediente?->nombre ?? 'N/D',
                'gramos_por_kg' => (float) $l->gramos_por_kg,
                'stock' => $l->ingrediente?->stock_gramos() ?? 0,
            ])->values();
            $esKg = $materia->unidad_base === 'kg';
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

            <form method="POST" action="{{ route('inventario.materia-prima.producir-molido.store', $materia) }}">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Molido a producir</label>
                    <div class="input-glass bg-gray-900/40 text-white/80">
                        {{ $materia->nombre }} ({{ $materia->codigo }})
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Cantidad a producir ({{ $materia->unidad_base }}) *</label>
                    <input type="number" name="cantidad" id="cantidadInput"
                           min="{{ $esKg ? '0.001' : '1' }}" step="{{ $esKg ? '0.001' : '1' }}"
                           value="{{ old('cantidad') }}" required class="input-glass">
                </div>

                <!-- Desglose de ingredientes por consumir -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-3">Ingredientes que se descontarán</label>
                    <div class="bg-gray-900/50 border border-white/10 rounded-xl p-4 space-y-2" id="desgloseLista"></div>
                    <p class="text-white/40 text-xs mt-2">Las cantidades se calculan según los gramos por kg definidos en la receta del molido.</p>
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
                        <i class="fas fa-mortar-pestle"></i>
                        Producir
                    </button>
                    <a href="{{ route('inventario.materia-prima.show', $materia) }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const ingredientes = @json($ingredientesJson);
    const esKg = @json($esKg);
    const cantidad = document.getElementById('cantidadInput');
    const lista = document.getElementById('desgloseLista');

    function formatear(valor) {
        return Number(valor).toLocaleString('es', { minimumFractionDigits: 0, maximumFractionDigits: 3 });
    }

    function actualizar() {
        const valor = Number(cantidad.value) || 0;
        const kg = esKg ? valor : valor / 1000;

        if (kg <= 0 || ingredientes.length === 0) {
            lista.innerHTML = '<p class="text-white/40 text-sm">Indica una cantidad para ver el desglose.</p>';
            return;
        }

        const filas = ingredientes.map(ing => {
            const gramos = ing.gramos_por_kg * kg;
            const insuficiente = gramos > ing.stock;
            const kgTexto = (gramos / 1000).toFixed(3);
            return `<div class="flex items-center justify-between text-sm gap-3">
                        <span class="text-white/70">${ing.nombre}</span>
                        <span class="text-right">
                            <span class="text-white font-medium">${formatear(gramos)} g</span>
                            <span class="text-white/40 text-xs">(${kgTexto} kg)</span>
                            <span class="block text-xs ${insuficiente ? 'text-red-400' : 'text-white/40'}">
                                stock: ${formatear(ing.stock)} g ${insuficiente ? '— insuficiente' : ''}
                            </span>
                        </span>
                    </div>`;
        });

        lista.innerHTML = filas.join('');
    }

    cantidad.addEventListener('input', actualizar);
    actualizar();
})();
</script>
@endpush

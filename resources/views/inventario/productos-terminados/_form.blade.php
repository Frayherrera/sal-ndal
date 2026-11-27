{{-- Formulario común de producto + presentaciones. Requiere $producto (Producto, nuevo o existente). --}}
@php
    use App\Models\ProductoTerminado;

    $filas = old('presentaciones');
    if ($filas === null) {
        $filas = $producto->exists
            ? $producto->presentaciones->map(fn ($p) => [
                'id' => $p->id,
                'categoria' => $p->categoria,
                'codigo' => $p->codigo,
                'presentacion' => $p->presentacion ?: 'bolsa',
                'peso_neto' => (float) $p->peso_neto ?: '',
                'precio_venta' => (float) $p->precio_venta ?: '',
                'stock_minimo' => (float) $p->stock_minimo ?: '',
                'activo' => $p->activo ? 1 : 0,
            ])->all()
            : [
                ['categoria' => 'B2C', 'presentacion' => 'bolsa', 'activo' => 1],
                ['categoria' => 'ESP', 'presentacion' => 'bolsa', 'activo' => 1],
            ];
    }
    $filas = array_values($filas);
@endphp

@if ($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6">
        <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="mb-6">
    <label class="block text-sm font-medium text-white/80 mb-2">Nombre del producto *</label>
    <input type="text" name="nombre" value="{{ old('nombre', $producto->nombre) }}" required maxlength="120"
           class="input-glass" placeholder="Ej: COMINO MOLIDO">
    <p class="text-white/40 text-xs mt-1">Un solo nombre para todas sus presentaciones.</p>
</div>

<div class="flex items-center justify-between mb-3">
    <h2 class="text-sm font-semibold text-white/80 uppercase tracking-wide">Presentaciones</h2>
    <button type="button" id="agregarPresentacion"
            class="flex items-center gap-2 text-sm text-blue-300 hover:text-blue-200 transition-colors">
        <i class="fas fa-plus"></i> Agregar presentación
    </button>
</div>

<div id="presentaciones" class="space-y-3">
    @foreach ($filas as $i => $f)
        @include('inventario.productos-terminados._presentacion', ['i' => $i, 'f' => $f])
    @endforeach
</div>

<p class="text-white/40 text-xs mt-3">
    Precio por paquete de {{ ProductoTerminado::UNIDADES_POR_PAQUETE }} unidades. El mínimo es en unidades.
    Si quitas una presentación que ya tiene movimientos, se desactiva en vez de borrarse.
</p>

<template id="plantillaPresentacion">
    @include('inventario.productos-terminados._presentacion', ['i' => '__I__', 'f' => ['categoria' => 'ESP', 'presentacion' => 'bolsa', 'activo' => 1]])
</template>

@push('scripts')
<script>
(function () {
    const contenedor = document.getElementById('presentaciones');
    const plantilla = document.getElementById('plantillaPresentacion');
    let siguiente = {{ count($filas) }};

    document.getElementById('agregarPresentacion').addEventListener('click', function () {
        const html = plantilla.innerHTML.replaceAll('__I__', siguiente++);
        contenedor.insertAdjacentHTML('beforeend', html);
    });

    contenedor.addEventListener('click', function (e) {
        const boton = e.target.closest('.quitar-presentacion');
        if (!boton) return;
        if (contenedor.querySelectorAll('.fila-presentacion').length <= 1) {
            alert('El producto debe tener al menos una presentación.');
            return;
        }
        boton.closest('.fila-presentacion').remove();
    });
})();
</script>
@endpush

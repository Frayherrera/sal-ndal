@extends('layouts.app')

@section('title', 'Nuevo Movimiento')

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.movimientos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Registrar Movimiento</h1>
        </div>

        @php
            $tiposMateriaPrima = ['compra_recepcion', 'consumo_produccion', 'ajuste_positivo', 'ajuste_negativo'];
            $tiposProducto = ['producto_producido', 'venta_despacho'];
            $tiposAjuste = ['ajuste_positivo', 'ajuste_negativo'];
            $preseleccion = request('tipo', 'compra_recepcion');
        @endphp

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('inventario.movimientos.store') }}" id="movimientoForm">
                @csrf

                @if ($errors->any())
                    <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 mb-6">
                        <ul class="list-disc list-inside text-red-300 text-sm space-y-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- Tipo -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Tipo de movimiento *</label>
                    <select name="tipo" id="tipoSelect" class="input-glass bg-gray-900/60">
                        @foreach ($tipos as $key => $label)
                            <option value="{{ $key }}" @selected(old('tipo', $preseleccion) === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Materia prima (visible si tipo es de MP) -->
                <div class="mb-5" id="campoMateria">
                    <label class="block text-sm font-medium text-white/80 mb-2">Materia prima *</label>
                    <select name="materia_prima_id" id="materiaSelect" class="input-glass bg-gray-900/60">
                        <option value="">Seleccionar materia prima...</option>
                        @foreach ($materiasPrimas as $mp)
                            <option value="{{ $mp->id }}" data-kg="{{ $mp->stock_kg() }}" data-unidad="{{ $mp->unidad_base }}"
                                    @selected(old('materia_prima_id') == $mp->id)>
                                {{ $mp->nombre }} ({{ $mp->codigo }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Producto terminado (visible si tipo es de PT) -->
                <div class="mb-5 hidden" id="campoProducto">
                    <label class="block text-sm font-medium text-white/80 mb-2">Producto terminado *</label>
                    <select name="producto_terminado_id" id="productoSelect" class="input-glass bg-gray-900/60">
                        <option value="">Seleccionar producto...</option>
                        @foreach ($productos as $pt)
                            <option value="{{ $pt->id }}" @selected(old('producto_terminado_id') == $pt->id)>
                                {{ $pt->nombre }} ({{ $pt->codigo }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Cantidad -->
                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Cantidad *</label>
                    <input type="number" step="any" min="0" name="cantidad" id="cantidadInput"
                           value="{{ old('cantidad') }}" required class="input-glass">
                    <p class="text-white/40 text-xs mt-1" id="cantidadHint">
                        Para materia prima, cantidad en kilogramos. Para productos, en unidades.
                    </p>
                </div>

                <!-- Dirección de devolución -->
                <div class="mb-5 hidden" id="campoDevolucion">
                    <label class="block text-sm font-medium text-white/80 mb-2">Dirección de la devolución *</label>
                    <select name="direccion_devolucion" class="input-glass bg-gray-900/60">
                        <option value="entrada">Entrada (devuelven producto)</option>
                        <option value="salida">Salida (se devuelve a proveedor)</option>
                    </select>
                </div>

                <div class="grid sm:grid-cols-2 gap-5 mb-5">
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Documento</label>
                        <input type="text" name="documento" value="{{ old('documento') }}"
                               class="input-glass" placeholder="Factura, remisión...">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Fecha</label>
                        <input type="datetime-local" name="fecha" value="{{ old('fecha', now()->format('Y-m-d\TH:i')) }}"
                               class="input-glass bg-gray-900/60">
                    </div>
                </div>

                <!-- Campos de costo (solo compra) -->
                <div class="grid sm:grid-cols-2 gap-5 mb-5 hidden" id="campoCosto">
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Costo total ($)</label>
                        <input type="number" step="0.01" min="0" name="costo_total" value="{{ old('costo_total') }}"
                               class="input-glass">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Referencia</label>
                        <input type="text" name="referencia" value="{{ old('referencia') }}"
                               class="input-glass" placeholder="Opcional">
                    </div>
                </div>

                <!-- Motivo (requerido para ajustes y anulaciones) -->
                <div class="mb-5" id="campoMotivo">
                    <label class="block text-sm font-medium text-white/80 mb-2">Motivo <span id="motivoReq"></span></label>
                    <textarea name="motivo" rows="2" class="input-glass" placeholder="Obligatorio para ajustes y devoluciones">{{ old('motivo') }}</textarea>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-check"></i>
                        Registrar Movimiento
                    </button>
                    <a href="{{ route('inventario.movimientos.index') }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const tipoSelect = document.getElementById('tipoSelect');
    const campoMateria = document.getElementById('campoMateria');
    const campoProducto = document.getElementById('campoProducto');
    const campoCosto = document.getElementById('campoCosto');
    const campoDevolucion = document.getElementById('campoDevolucion');
    const campoMotivo = document.getElementById('campoMotivo');
    const motivoReq = document.getElementById('motivoReq');
    const cantidadHint = document.getElementById('cantidadHint');
    const cantidadInput = document.getElementById('cantidadInput');

    const tip = {
        materia: @json($tiposMateriaPrima),
        producto: @json($tiposProducto),
        ajuste: @json($tiposAjuste),
    };

    function actualizar() {
        const tipo = tipoSelect.value;
        const esMateria = tip.materia.includes(tipo);
        const esProducto = tip.producto.includes(tipo);
        const esAjuste = tip.ajuste.includes(tipo);
        const esDevolucion = tipo === 'devolucion';
        const esCompra = tipo === 'compra_recepcion';

        campoMateria.classList.toggle('hidden', !esMateria);
        campoProducto.classList.toggle('hidden', !esProducto);
        campoCosto.classList.toggle('hidden', !esCompra && !esAjuste);
        campoDevolucion.classList.toggle('hidden', !esDevolucion);

        if (esAjuste) {
            motivoReq.textContent = '*';
            campoMotivo.classList.remove('hidden');
        } else if (esDevolucion) {
            motivoReq.textContent = '*';
            campoMotivo.classList.remove('hidden');
        } else {
            motivoReq.textContent = '';
        }

        if (esMateria) {
            cantidadHint.textContent = 'Para materia prima, ingresa la cantidad en kilogramos.';
        } else if (esProducto) {
            cantidadHint.textContent = 'Para productos terminados, ingresa la cantidad en unidades.';
        } else {
            cantidadHint.textContent = 'Cantidad a mover.';
        }

        if (esMateria && !esAjuste) {
            cantidadInput.step = '0.001';
        } else {
            cantidadInput.step = '1';
        }
        cantidadInput.min = '0';
    }

    tipoSelect.addEventListener('change', actualizar);
    actualizar();
})();
</script>
@endpush

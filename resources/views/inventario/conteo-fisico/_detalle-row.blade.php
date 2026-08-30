@php
    $itemId = $esMateria
        ? ($detalle->materia_prima_id ?? '')
        : ($detalle->producto_terminado_id ?? '');
    $selectedNombre = $esMateria
        ? ($detalle->materiaPrima?->nombre ?? '')
        : ($detalle->productoTerminado?->nombre ?? '');
    $unidadSistema = $esMateria ? $detalle->materiaPrima?->unidad_base : 'u';
@endphp

<div class="glass-card rounded-xl p-4 detail-row">
    <div class="flex flex-wrap gap-3 items-end">
        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-white/50 mb-1">{{ $esMateria ? 'Materia prima' : 'Producto' }}</label>
            <select name="detalles[][{{ $esMateria ? 'materia_prima_id' : 'producto_terminado_id' }}]"
                    class="input-glass bg-gray-900/60 item-select">
                <option value="">Seleccionar...</option>
                @if ($esMateria)
                    @foreach ($materiasPrimas as $item)
                        <option value="{{ $item->id }}" data-stock="{{ $item->stock_gramos() }}" @selected($item->id == $itemId)>
                            {{ $item->nombre }}
                        </option>
                    @endforeach
                @else
                    @foreach ($productos as $item)
                        <option value="{{ $item->id }}" data-stock="{{ $item->stock_disponible() }}" @selected($item->id == $itemId)>
                            {{ $item->nombre }}
                        </option>
                    @endforeach
                @endif
            </select>
        </div>

        <div class="w-28">
            <label class="block text-xs text-white/50 mb-1">Stock sistema</label>
            <input type="number" class="input-glass bg-gray-900/50 stock-sistema"
                   value="{{ $detalle->stock_sistema }}" readonly>
        </div>

        <div class="w-28">
            <label class="block text-xs text-white/50 mb-1">Stock físico</label>
            <input type="number" min="0" name="detalles[][cantidad_fisica]"
                   class="input-glass cantidad-fisica" value="{{ $detalle->cantidad_fisica }}">
        </div>

        <div class="flex-1 min-w-[200px]">
            <label class="block text-xs text-white/50 mb-1">Motivo (si hay diferencia)</label>
            <input type="text" name="detalles[][motivo]" class="input-glass motivo"
                   value="{{ $detalle->motivo }}" placeholder="Ej: merma, sobrante">
        </div>

        <button type="button" class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 remove-row">
            <i class="fas fa-trash"></i>
        </button>
    </div>
</div>

{{-- Una fila de presentación. Requiere $i (índice) y $f (datos). --}}
@php $n = "presentaciones[$i]"; @endphp
<div class="fila-presentacion glass-card rounded-xl p-4">
    @if (filled($f['id'] ?? null))
        <input type="hidden" name="{{ $n }}[id]" value="{{ $f['id'] }}">
    @endif
    <div class="grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
        <div>
            <label class="block text-xs text-white/60 mb-1">Línea *</label>
            <select name="{{ $n }}[categoria]" class="input-glass bg-gray-900/60" aria-label="Línea">
                @foreach (\App\Models\ProductoTerminado::LINEAS as $valor => $etiqueta)
                    <option value="{{ $valor }}" @selected(($f['categoria'] ?? '') === $valor)>{{ $etiqueta }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs text-white/60 mb-1">Peso (g)</label>
            <input type="number" step="0.01" min="0" name="{{ $n }}[peso_neto]" value="{{ $f['peso_neto'] ?? '' }}"
                   class="input-glass" placeholder="0" aria-label="Peso en gramos">
        </div>
        <div>
            <label class="block text-xs text-white/60 mb-1">Precio paquete</label>
            <input type="number" step="1" min="0" name="{{ $n }}[precio_venta]" value="{{ $f['precio_venta'] ?? '' }}"
                   class="input-glass" placeholder="0" aria-label="Precio del paquete">
        </div>
        <div>
            <label class="block text-xs text-white/60 mb-1">Mínimo (u)</label>
            <input type="number" step="1" min="0" name="{{ $n }}[stock_minimo]" value="{{ $f['stock_minimo'] ?? '' }}"
                   class="input-glass" placeholder="0" aria-label="Stock mínimo en unidades">
        </div>
        <div>
            <label class="block text-xs text-white/60 mb-1">Código</label>
            <input type="text" maxlength="30" name="{{ $n }}[codigo]" value="{{ $f['codigo'] ?? '' }}"
                   class="input-glass" placeholder="Auto" aria-label="Código">
        </div>
        <div class="flex items-center justify-between gap-2 pb-2">
            <label class="flex items-center gap-2 text-sm text-white/70 cursor-pointer">
                <input type="hidden" name="{{ $n }}[activo]" value="0">
                <input type="checkbox" name="{{ $n }}[activo]" value="1" @checked($f['activo'] ?? true)
                       class="w-4 h-4 rounded border-white/30 bg-white/10 text-blue-500">
                Activa
            </label>
            <button type="button" class="quitar-presentacion text-white/40 hover:text-red-300 transition-colors" title="Quitar presentación">
                <i class="fas fa-trash"></i>
            </button>
        </div>
    </div>
    <input type="hidden" name="{{ $n }}[presentacion]" value="{{ $f['presentacion'] ?? 'bolsa' }}">
</div>

{{-- Campos compartidos por crear y editar gasto. Requiere $gasto, $categorias y $metodos. --}}
@if ($errors->any())
    <div class="glass-card rounded-xl p-4 mb-6 border border-red-500/30 bg-red-500/10">
        <div class="flex items-start gap-3">
            <i class="fas fa-exclamation-circle text-red-400 mt-0.5"></i>
            <ul class="text-sm text-red-200 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    </div>
@endif

<div class="grid sm:grid-cols-2 gap-5">
    <div class="sm:col-span-2">
        <label for="monto" class="block text-sm font-medium text-white/80 mb-2">Valor *</label>
        <div class="relative">
            <span class="absolute left-4 top-1/2 -translate-y-1/2 text-white/50 text-xl font-semibold">$</span>
            <input type="number" id="monto" name="monto" required min="0.01" step="0.01" inputmode="decimal"
                   value="{{ old('monto', $gasto->monto !== null ? (float) $gasto->monto : null) }}" placeholder="0"
                   class="input-glass text-2xl font-bold pl-10 py-3">
        </div>
        @error('monto') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div class="sm:col-span-2">
        <label for="descripcion" class="block text-sm font-medium text-white/80 mb-2">Descripción *</label>
        <input type="text" id="descripcion" name="descripcion" required maxlength="255"
               value="{{ old('descripcion', $gasto->descripcion) }}"
               class="input-glass" placeholder="Ej: Factura de luz de octubre">
        @error('descripcion') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="categoria_gasto_id" class="block text-sm font-medium text-white/80 mb-2">Categoría *</label>
        <select id="categoria_gasto_id" name="categoria_gasto_id" required class="input-glass bg-gray-900/60">
            <option value="">Seleccionar categoría...</option>
            @foreach ($categorias as $categoria)
                <option value="{{ $categoria->id }}" @selected((string) old('categoria_gasto_id', $gasto->categoria_gasto_id) === (string) $categoria->id)>
                    {{ $categoria->nombre }}{{ $categoria->activo ? '' : ' (inactiva)' }}
                </option>
            @endforeach
        </select>
        @error('categoria_gasto_id') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
        <a href="{{ route('gastos.categorias.create') }}" class="inline-block text-xs text-blue-300 hover:text-blue-200 mt-1">
            <i class="fas fa-plus mr-1"></i>Nueva categoría
        </a>
    </div>

    <div>
        <label for="fecha" class="block text-sm font-medium text-white/80 mb-2">Fecha *</label>
        <input type="date" id="fecha" name="fecha" required max="{{ now()->toDateString() }}"
               value="{{ old('fecha', $gasto->fecha?->toDateString() ?? now()->toDateString()) }}" class="input-glass">
        @error('fecha') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="metodo_pago" class="block text-sm font-medium text-white/80 mb-2">Método de pago</label>
        <select id="metodo_pago" name="metodo_pago" class="input-glass bg-gray-900/60">
            <option value="">Sin especificar</option>
            @foreach ($metodos as $key => $label)
                <option value="{{ $key }}" @selected(old('metodo_pago', $gasto->exists ? $gasto->metodo_pago : 'efectivo') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @error('metodo_pago') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="proveedor" class="block text-sm font-medium text-white/80 mb-2">Proveedor / beneficiario</label>
        <input type="text" id="proveedor" name="proveedor" maxlength="120"
               value="{{ old('proveedor', $gasto->proveedor) }}"
               class="input-glass" placeholder="Opcional">
        @error('proveedor') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>
</div>

<p class="text-xs text-white/40 mt-5">
    <i class="fas fa-circle-info mr-1"></i>
    Las compras de materia prima no se registran aquí: se generan solas al registrar una compra con costo en Movimientos.
</p>

{{-- Campos compartidos por crear y editar categoría. Requiere $categoria. --}}
<div class="space-y-5">
    <div>
        <label for="nombre" class="block text-sm font-medium text-white/80 mb-2">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required maxlength="80"
               value="{{ old('nombre', $categoria->nombre) }}"
               class="input-glass" placeholder="Ej: Servicios públicos">
        @error('nombre') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="descripcion" class="block text-sm font-medium text-white/80 mb-2">Descripción</label>
        <input type="text" id="descripcion" name="descripcion" maxlength="255"
               value="{{ old('descripcion', $categoria->descripcion) }}"
               class="input-glass" placeholder="Opcional">
        @error('descripcion') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    @if ($categoria->esDelSistema())
        <input type="hidden" name="activo" value="1">
        <p class="text-xs text-purple-300">
            <i class="fas fa-bolt mr-1"></i>
            Categoría automática: recibe los gastos de las compras de materia prima y siempre está activa.
        </p>
    @else
        <label class="flex items-center gap-3 cursor-pointer select-none">
            <input type="hidden" name="activo" value="0">
            <input type="checkbox" name="activo" value="1" class="w-4 h-4 rounded accent-blue-500"
                   @checked(old('activo', $categoria->activo ?? true))>
            <span class="text-sm text-white/80">Activa (disponible al registrar gastos)</span>
        </label>
    @endif
</div>

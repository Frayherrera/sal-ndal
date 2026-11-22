{{-- Campos compartidos de la ruta. Requiere $ruta y $ciudades (ciudades de los clientes). --}}
@php
    $seleccionadas = old('ciudades', $ruta->ciudades ?? []);
    // Ciudades guardadas que ya no tiene ningún cliente también se muestran
    $opciones = collect($ciudades)->merge($ruta->ciudades ?? [])->unique()->sort()->values();
@endphp

@if ($errors->any())
    <div class="glass-card rounded-xl p-4 mb-6 border border-red-500/30 bg-red-500/10">
        <ul class="text-sm text-red-200 space-y-1">
            @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
        </ul>
    </div>
@endif

<div class="grid sm:grid-cols-4 gap-5">
    <div class="sm:col-span-3">
        <label for="nombre" class="block text-sm font-medium text-white/80 mb-2">Nombre *</label>
        <input type="text" id="nombre" name="nombre" required maxlength="80" value="{{ old('nombre', $ruta->nombre) }}"
               class="input-glass" placeholder="Ej: Pelaya – La Gloria">
    </div>
    <div>
        <label for="orden" class="block text-sm font-medium text-white/80 mb-2">Orden *</label>
        <input type="number" id="orden" name="orden" required min="1" max="999" value="{{ old('orden', $ruta->orden) }}" class="input-glass">
    </div>

    <div class="sm:col-span-4">
        <span class="block text-sm font-medium text-white/80 mb-2">Ciudades que visita</span>
        @if ($opciones->isEmpty())
            <p class="text-sm text-white/40">Tus clientes aún no tienen ciudad registrada.</p>
        @else
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                @foreach ($opciones as $ciudad)
                    <label class="flex items-center gap-2 p-2 rounded-lg bg-white/5 text-sm text-white/80 cursor-pointer hover:bg-white/10">
                        <input type="checkbox" name="ciudades[]" value="{{ $ciudad }}" class="w-4 h-4 accent-blue-500"
                               @checked(in_array($ciudad, $seleccionadas, true))>
                        {{ $ciudad }}
                    </label>
                @endforeach
            </div>
        @endif
        <p class="text-xs text-white/40 mt-2">Se usan para comparar lo vendido por el camión con las ventas registradas a clientes de esas ciudades.</p>
    </div>

    <div class="sm:col-span-4">
        <label for="descripcion" class="block text-sm font-medium text-white/80 mb-2">Descripción</label>
        <input type="text" id="descripcion" name="descripcion" maxlength="255" value="{{ old('descripcion', $ruta->descripcion) }}"
               class="input-glass" placeholder="Opcional">
    </div>

    <label class="sm:col-span-4 flex items-center gap-3 cursor-pointer select-none">
        <input type="hidden" name="activo" value="0">
        <input type="checkbox" name="activo" value="1" class="w-4 h-4 accent-blue-500" @checked(old('activo', $ruta->activo ?? true))>
        <span class="text-sm text-white/80">Activa (entra en la rotación)</span>
    </label>
</div>

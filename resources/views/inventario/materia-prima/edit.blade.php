@extends('layouts.app')

@section('title', 'Editar Materia Prima')

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.materia-prima.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Editar: {{ $materia->nombre }}</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('inventario.materia-prima.update', $materia) }}">
                @csrf
                @method('PUT')

                <div class="grid sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-white/80 mb-2">Nombre *</label>
                        <input type="text" name="nombre" value="{{ old('nombre', $materia->nombre) }}" required
                               class="input-glass">
                        @error('nombre') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Código</label>
                        <input type="text" name="codigo" value="{{ old('codigo', $materia->codigo) }}"
                               class="input-glass" {{ $materia->movimientos()->exists() ? 'readonly' : '' }}>
                        @error('codigo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Categoría</label>
                        <input type="text" name="categoria" value="{{ old('categoria', $materia->categoria) }}"
                               class="input-glass">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Unidad base *</label>
                        <select name="unidad_base" class="input-glass bg-gray-900/60" {{ $materia->movimientos()->exists() ? 'disabled' : '' }}>
                            <option value="kg" @selected(old('unidad_base', $materia->unidad_base) === 'kg')>Kilogramos (kg)</option>
                            <option value="g" @selected(old('unidad_base', $materia->unidad_base) === 'g')>Gramos (g)</option>
                        </select>
                        @if ($materia->movimientos()->exists())
                            <input type="hidden" name="unidad_base" value="{{ $materia->unidad_base }}">
                            <p class="text-white/40 text-xs mt-1">La unidad no cambia con movimientos registrados.</p>
                        @endif
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Stock mínimo</label>
                        <input type="number" step="0.01" min="0" name="stock_minimo" value="{{ old('stock_minimo', $materia->stock_minimo) }}"
                               class="input-glass">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Proveedor</label>
                        <input type="text" name="proveedor" value="{{ old('proveedor', $materia->proveedor) }}"
                               class="input-glass">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Ubicación / Bodega</label>
                        <input type="text" name="ubicacion" value="{{ old('ubicacion', $materia->ubicacion) }}"
                               class="input-glass">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" name="activo" value="1"
                                   @checked(old('activo', $materia->activo))
                                   class="w-5 h-5 rounded border-white/30 bg-white/10 text-blue-500 focus:ring-blue-500">
                            <span class="text-white/80 text-sm">Materia prima activa</span>
                        </label>
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Actualizar
                    </button>
                    <a href="{{ route('inventario.materia-prima.show', $materia) }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

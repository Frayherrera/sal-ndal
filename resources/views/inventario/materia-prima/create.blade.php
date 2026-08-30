@extends('layouts.app')

@section('title', 'Nueva Materia Prima')

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.materia-prima.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Nueva Materia Prima</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('inventario.materia-prima.store') }}">
                @csrf

                <div class="grid sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-white/80 mb-2">Nombre *</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required
                               class="input-glass" placeholder="Ej: Orégano, Comino, Pimentón">
                        @error('nombre') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Código</label>
                        <input type="text" name="codigo" value="{{ old('codigo') }}"
                               class="input-glass" placeholder="Dejar vacío para autogenerar">
                        @error('codigo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Categoría</label>
                        <input type="text" name="categoria" value="{{ old('categoria') }}"
                               class="input-glass" placeholder="Ej: Hierbas, Semillas">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Unidad base *</label>
                        <select name="unidad_base" class="input-glass bg-gray-900/60">
                            <option value="kg" @selected(old('unidad_base', 'kg') === 'kg')>Kilogramos (kg)</option>
                            <option value="g" @selected(old('unidad_base') === 'g')>Gramos (g)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Stock mínimo</label>
                        <input type="number" step="0.01" min="0" name="stock_minimo" value="{{ old('stock_minimo', 0) }}"
                               class="input-glass">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Proveedor</label>
                        <input type="text" name="proveedor" value="{{ old('proveedor') }}"
                               class="input-glass" placeholder="Opcional">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Ubicación / Bodega</label>
                        <input type="text" name="ubicacion" value="{{ old('ubicacion') }}"
                               class="input-glass" placeholder="Opcional">
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Guardar Materia Prima
                    </button>
                    <a href="{{ route('inventario.materia-prima.index') }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

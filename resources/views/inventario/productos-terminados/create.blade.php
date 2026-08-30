@extends('layouts.app')

@section('title', 'Nuevo Producto Terminado')

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.productos-terminados.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Nuevo Producto Terminado</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('inventario.productos-terminados.store') }}">
                @csrf

                <div class="grid sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-white/80 mb-2">Nombre *</label>
                        <input type="text" name="nombre" value="{{ old('nombre') }}" required
                               class="input-glass" placeholder="Ej: Comino 100g, Orégano 30g">
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
                               class="input-glass" placeholder="Ej: Hierbas, Condimentos">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Presentación</label>
                        <select name="presentacion" class="input-glass bg-gray-900/60">
                            <option value="">Seleccionar...</option>
                            <option value="bolsa" @selected(old('presentacion') === 'bolsa')>Bolsa</option>
                            <option value="frasco" @selected(old('presentacion') === 'frasco')>Frasco</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Peso neto (g)</label>
                        <input type="number" step="0.01" min="0" name="peso_neto" value="{{ old('peso_neto', 50) }}"
                               class="input-glass">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Precio de venta ($)</label>
                        <input type="number" step="0.01" min="0" name="precio_venta" value="{{ old('precio_venta', 0) }}"
                               class="input-glass">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-white/80 mb-2">Stock mínimo (unidades)</label>
                        <input type="number" step="0.01" min="0" name="stock_minimo" value="{{ old('stock_minimo', 0) }}"
                               class="input-glass">
                    </div>

                    <div class="sm:col-span-2">
                        <label class="block text-sm font-medium text-white/80 mb-2">URL de imagen (opcional)</label>
                        <input type="text" name="imagen" value="{{ old('imagen') }}"
                               class="input-glass" placeholder="https://...">
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Guardar Producto
                    </button>
                    <a href="{{ route('inventario.productos-terminados.index') }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

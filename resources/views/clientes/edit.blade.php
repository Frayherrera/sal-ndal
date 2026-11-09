@extends('layouts.app')

@section('title', 'Editar Cliente')

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('clientes.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Editar Cliente</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('clientes.update', $cliente) }}">
                @csrf
                @method('PUT')

                <div class="grid sm:grid-cols-2 gap-5">
                    <div class="sm:col-span-2">
                        <label for="nombre" class="block text-sm font-medium text-white/80 mb-2">Nombre *</label>
                        <input type="text" id="nombre" name="nombre" value="{{ old('nombre', $cliente->nombre) }}" required
                               class="input-glass" placeholder="Ej: María Gómez, Distribuidora El Sol">
                        @error('nombre') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="local" class="block text-sm font-medium text-white/80 mb-2">Local</label>
                        <input type="text" id="local" name="local" value="{{ old('local', $cliente->local) }}"
                               class="input-glass" placeholder="Ej: Tienda La Esquina">
                        @error('local') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="ciudad" class="block text-sm font-medium text-white/80 mb-2">Ciudad</label>
                        <input type="text" id="ciudad" name="ciudad" value="{{ old('ciudad', $cliente->ciudad) }}"
                               class="input-glass" placeholder="Ej: Bogotá">
                        @error('ciudad') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="sm:col-span-2">
                        <label for="telefono" class="block text-sm font-medium text-white/80 mb-2">Teléfono</label>
                        <input type="tel" id="telefono" name="telefono" value="{{ old('telefono', $cliente->telefono) }}"
                               class="input-glass" placeholder="Ej: 300 123 4567">
                        @error('telefono') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <a href="{{ route('clientes.index') }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

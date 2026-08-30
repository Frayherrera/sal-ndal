@extends('layouts.app')

@section('title', 'Nuevo Conteo Físico')

@section('content')
    <div class="max-w-3xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.conteo-fisico.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Nuevo Conteo Físico</h1>
        </div>

        @php
            $esMateria = $tipo === 'materia_prima';
        @endphp

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('inventario.conteo-fisico.store') }}">
                @csrf
                <input type="hidden" name="tipo" value="{{ $tipo }}">

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Tipo de inventario</label>
                    <div class="flex gap-3">
                        <a href="{{ route('inventario.conteo-fisico.create', ['tipo' => 'materia_prima']) }}"
                           class="flex-1 px-4 py-3 rounded-xl text-sm font-medium text-center transition-colors {{ $esMateria ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'bg-white/5 text-white/60 hover:bg-white/10' }}">
                            Materia prima
                        </a>
                        <a href="{{ route('inventario.conteo-fisico.create', ['tipo' => 'producto_terminado']) }}"
                           class="flex-1 px-4 py-3 rounded-xl text-sm font-medium text-center transition-colors {{ !$esMateria ? 'bg-blue-500/20 text-blue-300 border border-blue-500/30' : 'bg-white/5 text-white/60 hover:bg-white/10' }}">
                            Producto terminado
                        </a>
                    </div>
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Fecha de conteo *</label>
                    <input type="date" name="fecha_conteo" value="{{ old('fecha_conteo', now()->format('Y-m-d')) }}" required
                           class="input-glass bg-gray-900/60">
                </div>

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Observaciones</label>
                    <textarea name="observaciones" rows="2" class="input-glass">{{ old('observaciones') }}</textarea>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-clipboard"></i>
                        Crear Conteo
                    </button>
                    <a href="{{ route('inventario.conteo-fisico.index') }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

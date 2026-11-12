@extends('layouts.app')

@section('title', 'Editar Categoría de Gasto')

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('gastos.categorias.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Editar Categoría</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('gastos.categorias.update', $categoria) }}">
                @csrf
                @method('PUT')

                @include('gastos.categorias._form')

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <a href="{{ route('gastos.categorias.index') }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

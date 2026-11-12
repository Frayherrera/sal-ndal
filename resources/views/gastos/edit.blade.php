@extends('layouts.app')

@section('title', 'Editar Gasto '.$gasto->numero)

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('gastos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">Editar Gasto</h1>
                <p class="text-white/50 text-sm font-mono">{{ $gasto->numero }}</p>
            </div>
        </div>

        <div class="glass rounded-2xl p-8">
            <form method="POST" action="{{ route('gastos.update', $gasto) }}">
                @csrf
                @method('PUT')

                @include('gastos._form')

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Guardar Cambios
                    </button>
                    <a href="{{ route('gastos.index') }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                    <a href="{{ route('gastos.anular', $gasto) }}" class="ml-auto text-red-300 hover:text-red-200 px-4 py-3 transition-colors text-sm">
                        <i class="fas fa-ban mr-1"></i> Anular gasto
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection

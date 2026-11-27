@extends('layouts.app')

@section('title', 'Editar Producto')

@section('content')
    <div class="max-w-4xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.productos-terminados.show', $producto) }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Editar {{ $producto->nombre }}</h1>
        </div>

        <div class="glass rounded-2xl p-6 sm:p-8">
            <form method="POST" action="{{ route('inventario.productos-terminados.update', $producto) }}">
                @csrf
                @method('PUT')

                @include('inventario.productos-terminados._form')

                <div class="mt-6">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="hidden" name="activo" value="0">
                        <input type="checkbox" name="activo" value="1" @checked(old('activo', $producto->activo))
                               class="w-5 h-5 rounded border-white/30 bg-white/10 text-blue-500 focus:ring-blue-500">
                        <span class="text-white/80 text-sm">Producto activo</span>
                    </label>
                </div>

                <div class="mt-8 flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i>
                        Actualizar
                    </button>
                    <a href="{{ route('inventario.productos-terminados.show', $producto) }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

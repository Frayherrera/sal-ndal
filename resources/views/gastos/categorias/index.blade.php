@extends('layouts.app')

@section('title', 'Categorías de Gastos')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('gastos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Categorías de Gastos</h1>
                <p class="text-white/60">Organiza tus gastos. Las categorías con gastos se desactivan en lugar de eliminarse.</p>
            </div>
        </div>
        <a href="{{ route('gastos.categorias.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus"></i>
            <span>Nueva Categoría</span>
        </a>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($categorias->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-tags text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay categorías registradas.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Nombre</th>
                        <th class="p-4 hidden md:table-cell">Descripción</th>
                        <th class="p-4 text-right">Gastos</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($categorias as $categoria)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4">
                                <div class="text-white font-medium">{{ $categoria->nombre }}</div>
                                @if ($categoria->esDelSistema())
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded-full text-xs bg-purple-500/20 text-purple-300">
                                        <i class="fas fa-bolt mr-1"></i>Automática
                                    </span>
                                @endif
                            </td>
                            <td class="p-4 text-white/60 hidden md:table-cell">{{ $categoria->descripcion ?? '—' }}</td>
                            <td class="p-4 text-right text-white/80">{{ number_format($categoria->gastos_count) }}</td>
                            <td class="p-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $categoria->activo ? 'bg-green-500/20 text-green-300' : 'bg-white/10 text-white/50' }}">
                                    {{ $categoria->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('gastos.categorias.edit', $categoria) }}"
                                       class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-white/60 hover:text-white transition-colors"
                                       title="Editar">
                                        <i class="fas fa-pen text-sm"></i>
                                    </a>
                                    @if (! $categoria->esDelSistema() && $categoria->gastos_count === 0)
                                        <form method="POST" action="{{ route('gastos.categorias.destroy', $categoria) }}"
                                              onsubmit="return confirm('¿Eliminar la categoría {{ $categoria->nombre }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-red-300 hover:text-red-200 hover:bg-red-500/10 transition-colors"
                                                    title="Eliminar">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

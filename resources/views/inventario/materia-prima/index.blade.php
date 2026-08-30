@extends('layouts.app')

@section('title', 'Materias Primas')

@section('content')
    <div class="flex items-center justify-between mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Materias Primas</h1>
            <p class="text-white/60">Condimentos y especias almacenados para producción.</p>
        </div>
        <a href="{{ route('inventario.materia-prima.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus"></i>
            <span>Nueva Materia Prima</span>
        </a>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($materiasPrimas->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-seedling text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay materias primas registradas.</p>
                <a href="{{ route('inventario.materia-prima.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar la primera →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Código</th>
                        <th class="p-4">Nombre</th>
                        <th class="p-4">Categoría</th>
                        <th class="p-4 text-right">Stock</th>
                        <th class="p-4 text-right">Mínimo</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($materiasPrimas as $mp)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 font-mono text-blue-300">{{ $mp->codigo }}</td>
                            <td class="p-4 text-white font-medium">{{ $mp->nombre }}</td>
                            <td class="p-4 text-white/60">{{ $mp->categoria ?? '—' }}</td>
                            <td class="p-4 text-right">
                                <span class="text-white font-semibold">
                                    {{ number_format($mp->stock_kg(), ($mp->unidad_base === 'g' ? 0 : 2)) }} {{ $mp->unidad_base }}
                                </span>
                                @if ($mp->esStockBajo())
                                    <span class="block text-xs text-yellow-400 mt-0.5">Bajo</span>
                                @endif
                            </td>
                            <td class="p-4 text-right text-white/60">{{ number_format($mp->stock_minimo, 2) }} {{ $mp->unidad_base }}</td>
                            <td class="p-4 text-center">
                                <form method="POST" action="{{ route('inventario.materia-prima.toggle', $mp) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 rounded-full text-xs font-medium {{ $mp->activo ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300' }}">
                                        {{ $mp->activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </form>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('inventario.materia-prima.show', $mp) }}" title="Ver kardex"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition-colors">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('inventario.materia-prima.edit', $mp) }}" title="Editar"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-500/20 text-purple-300 hover:bg-purple-500/30 transition-colors">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    @if (!$mp->movimientos()->exists())
                                        <form method="POST" action="{{ route('inventario.materia-prima.destroy', $mp) }}"
                                              onsubmit="return confirm('¿Eliminar esta materia prima?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
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

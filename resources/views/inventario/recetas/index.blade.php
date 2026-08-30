@extends('layouts.app')

@section('title', 'Recetas de Producción')

@section('content')
    <div class="flex items-center justify-between mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Recetas de Producción</h1>
            <p class="text-white/60">Define qué materias primas y en qué cantidad consume cada producto terminado.</p>
        </div>
        <a href="{{ route('inventario.recetas.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus"></i>
            <span>Nueva Receta</span>
        </a>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($productos->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-book-open text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay productos terminados.</p>
                <a href="{{ route('inventario.recetas.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Crear una receta →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Código</th>
                        <th class="p-4">Producto</th>
                        <th class="p-4">Peso neto</th>
                        <th class="p-4">Ingredientes</th>
                        <th class="p-4 text-right">Gramos / unidad</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $producto)
                        @php $totalG = $producto->gramosPorUnidad(); @endphp
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 font-mono text-blue-300">{{ $producto->codigo }}</td>
                            <td class="p-4 text-white font-medium">{{ $producto->nombre }}</td>
                            <td class="p-4 text-white/60">{{ $producto->peso_neto }} g</td>
                            <td class="p-4 text-white/60">{{ $producto->receta->count() }} ingrediente(s)</td>
                            <td class="p-4 text-right text-white font-semibold">{{ number_format($totalG, 3) }} g</td>
                            <td class="p-4 text-center">
                                @if ($producto->receta->isNotEmpty())
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-500/20 text-green-300">Con receta</span>
                                @else
                                    <span class="px-2 py-1 rounded-full text-xs font-medium bg-yellow-500/20 text-yellow-300">Sin receta</span>
                                @endif
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($producto->receta->isNotEmpty())
                                        <a href="{{ route('inventario.recetas.edit', $producto) }}" title="Editar receta"
                                           class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-500/20 text-purple-300 hover:bg-purple-500/30 transition-colors">
                                            <i class="fas fa-edit text-sm"></i>
                                        </a>
                                        <form method="POST" action="{{ route('inventario.recetas.destroy', $producto) }}"
                                              onsubmit="return confirm('¿Eliminar la receta de este producto?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Eliminar receta"
                                                    class="w-8 h-8 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 transition-colors">
                                                <i class="fas fa-trash text-sm"></i>
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('inventario.recetas.edit', $producto) }}" title="Definir receta"
                                           class="flex items-center gap-2 px-3 py-1.5 rounded-lg bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition-colors text-xs font-medium">
                                            <i class="fas fa-plus"></i>
                                            <span>Definir</span>
                                        </a>
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

@extends('layouts.app')

@section('title', 'Productos Terminados')

@section('content')
    <div class="flex items-center justify-between mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Productos Terminados</h1>
            <p class="text-white/60">Presentaciones comerciales empacadas y listas para la venta.</p>
        </div>
        <a href="{{ route('inventario.productos-terminados.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus"></i>
            <span>Nuevo Producto</span>
        </a>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($productos->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-box-open text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay productos terminados registrados.</p>
                <a href="{{ route('inventario.productos-terminados.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar el primero →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Código</th>
                        <th class="p-4">Nombre</th>
                        <th class="p-4">Presentación</th>
                        <th class="p-4 text-right">Peso neto</th>
                        <th class="p-4 text-right">Disponible</th>
                        <th class="p-4 text-right">Precio</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($productos as $pt)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 font-mono text-blue-300">{{ $pt->codigo }}</td>
                            <td class="p-4 text-white font-medium">{{ $pt->nombre }}</td>
                            <td class="p-4 text-white/60 capitalize">{{ $pt->presentacion ?? '—' }}</td>
                            <td class="p-4 text-right text-white/60">{{ number_format($pt->peso_neto, 0) }} g</td>
                            <td class="p-4 text-right">
                                <span class="text-white font-semibold">{{ number_format($pt->stock_disponible()) }} u</span>
                                @if ($pt->esStockBajo())
                                    <span class="block text-xs text-yellow-400 mt-0.5">Bajo</span>
                                @endif
                            </td>
                            <td class="p-4 text-right text-white/80">${{ number_format($pt->precio_venta, 2) }}</td>
                            <td class="p-4 text-center">
                                <form method="POST" action="{{ route('inventario.productos-terminados.toggle', $pt) }}">
                                    @csrf
                                    <button type="submit"
                                            class="px-2 py-1 rounded-full text-xs font-medium {{ $pt->activo ? 'bg-green-500/20 text-green-300' : 'bg-gray-500/20 text-gray-300' }}">
                                        {{ $pt->activo ? 'Activo' : 'Inactivo' }}
                                    </button>
                                </form>
                            </td>
                            <td class="p-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('inventario.productos-terminados.show', $pt) }}" title="Ver kardex"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-500/20 text-blue-300 hover:bg-blue-500/30 transition-colors">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                    <a href="{{ route('inventario.productos-terminados.edit', $pt) }}" title="Editar"
                                       class="w-8 h-8 flex items-center justify-center rounded-lg bg-purple-500/20 text-purple-300 hover:bg-purple-500/30 transition-colors">
                                        <i class="fas fa-edit text-sm"></i>
                                    </a>
                                    @if (!$pt->movimientos()->exists())
                                        <form method="POST" action="{{ route('inventario.productos-terminados.destroy', $pt) }}"
                                              onsubmit="return confirm('¿Eliminar este producto?')">
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

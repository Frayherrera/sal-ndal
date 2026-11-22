@extends('layouts.app')

@section('title', 'Rutas del camión')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('camion.index') }}" class="text-white/60 hover:text-white transition-colors"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Rutas del camión</h1>
                <p class="text-white/60">Rutas fijas en el orden en que se rotan cada semana.</p>
            </div>
        </div>
        <a href="{{ route('camion.rutas.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus"></i><span>Nueva ruta</span>
        </a>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($rutas->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-route text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">Todavía no hay rutas.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4 w-16">Orden</th>
                        <th class="p-4">Ruta</th>
                        <th class="p-4 hidden md:table-cell">Ciudades</th>
                        <th class="p-4 text-right">Viajes</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rutas as $ruta)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 text-white/70 font-mono">{{ $ruta->orden }}</td>
                            <td class="p-4">
                                <div class="text-white font-medium">{{ $ruta->nombre }}</div>
                                @if ($ruta->descripcion)<div class="text-xs text-white/40">{{ $ruta->descripcion }}</div>@endif
                            </td>
                            <td class="p-4 text-white/60 hidden md:table-cell">{{ implode(', ', $ruta->ciudades ?? []) ?: '—' }}</td>
                            <td class="p-4 text-right text-white/80">{{ $ruta->viajes_count }}</td>
                            <td class="p-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs {{ $ruta->activo ? 'bg-green-500/20 text-green-300' : 'bg-white/10 text-white/50' }}">
                                    {{ $ruta->activo ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('camion.rutas.edit', $ruta) }}" title="Editar"
                                       class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-white/60 hover:text-white">
                                        <i class="fas fa-pen text-sm"></i>
                                    </a>
                                    @if ($ruta->viajes_count === 0)
                                        <form method="POST" action="{{ route('camion.rutas.destroy', $ruta) }}" onsubmit="return confirm('¿Eliminar la ruta {{ $ruta->nombre }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" title="Eliminar"
                                                    class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-red-300 hover:text-red-200 hover:bg-red-500/10">
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

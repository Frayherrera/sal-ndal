@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Clientes</h1>
            <p class="text-white/60">Tiendas, supermercados y restaurantes que compran nuestros productos.</p>
        </div>
        <a href="{{ route('clientes.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus"></i>
            <span>Nuevo Cliente</span>
        </a>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($clientes->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-users text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay clientes registrados.</p>
                <a href="{{ route('clientes.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar el primer cliente →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Nombre</th>
                        <th class="p-4 hidden sm:table-cell">Local</th>
                        <th class="p-4 hidden md:table-cell">Ciudad</th>
                        <th class="p-4 hidden sm:table-cell">Teléfono</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($clientes as $cliente)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 text-white font-medium">{{ $cliente->nombre }}</td>
                            <td class="p-4 text-white/60 hidden sm:table-cell">{{ $cliente->local ?? '—' }}</td>
                            <td class="p-4 text-white/60 hidden md:table-cell">{{ $cliente->ciudad ?? '—' }}</td>
                            <td class="p-4 hidden sm:table-cell">
                                @if ($cliente->telefono)
                                    <a href="tel:{{ $cliente->telefono }}" class="text-blue-300 hover:text-blue-200 transition-colors">
                                        {{ $cliente->telefono }}
                                    </a>
                                @else
                                    <span class="text-white/40">—</span>
                                @endif
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('clientes.edit', $cliente) }}"
                                       class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-white/60 hover:text-white transition-colors"
                                       title="Editar">
                                        <i class="fas fa-pen text-sm"></i>
                                    </a>
                                    <form method="POST" action="{{ route('clientes.destroy', $cliente) }}"
                                          onsubmit="return confirm('¿Eliminar a {{ $cliente->nombre }}?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-red-300 hover:text-red-200 hover:bg-red-500/10 transition-colors"
                                                title="Eliminar">
                                            <i class="fas fa-trash text-sm"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

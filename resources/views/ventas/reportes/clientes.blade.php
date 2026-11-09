@extends('layouts.app')

@section('title', 'Ventas por Cliente')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Ventas por Cliente</h1>
                <p class="text-white/60">Total vendido, abonado y saldo por cliente.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('ventas.reportes.productos') }}" class="text-sm text-purple-300 hover:text-purple-200 transition-colors">
                <i class="fas fa-ranking-star mr-1"></i> Productos más vendidos
            </a>
            <a href="{{ route('ventas.export', request()->query()) }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-file-csv"></i> CSV
            </a>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div>
                <label class="block text-xs text-white/50 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" class="input-glass">
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="input-glass">
            </div>
            <button type="submit" class="flex items-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            @if (request()->hasAny(['desde', 'hasta']))
                <a href="{{ route('ventas.reportes.clientes') }}" class="text-white/50 hover:text-white text-sm py-3">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($filas->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-users text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay ventas en el período seleccionado.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Cliente</th>
                        <th class="p-4 hidden md:table-cell">Local</th>
                        <th class="p-4 hidden lg:table-cell">Ciudad</th>
                        <th class="p-4 text-right">Ventas</th>
                        <th class="p-4 text-right">Total vendido</th>
                        <th class="p-4 text-right hidden sm:table-cell">Abonado</th>
                        <th class="p-4 text-right">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $fila)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4">
                                @if ($fila->cliente)
                                    <a href="{{ route('ventas.index', ['cliente_id' => $fila->cliente_id]) }}"
                                       class="text-white font-medium hover:text-blue-300 transition-colors">
                                        {{ $fila->cliente->nombre }}
                                    </a>
                                @else
                                    <span class="text-white/40">Cliente eliminado</span>
                                @endif
                            </td>
                            <td class="p-4 text-white/60 hidden md:table-cell">{{ $fila->cliente?->local ?? '—' }}</td>
                            <td class="p-4 text-white/60 hidden lg:table-cell">{{ $fila->cliente?->ciudad ?? '—' }}</td>
                            <td class="p-4 text-right text-white">{{ number_format($fila->ventas_count) }}</td>
                            <td class="p-4 text-right text-white font-medium">${{ number_format($fila->total_vendido, 0, ',', '.') }}</td>
                            <td class="p-4 text-right text-green-300 hidden sm:table-cell">${{ number_format($fila->total_pagado, 0, ',', '.') }}</td>
                            <td class="p-4 text-right font-semibold {{ $fila->saldo > 0 ? 'text-yellow-300' : 'text-white/40' }}">
                                ${{ number_format($fila->saldo, 0, ',', '.') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4">
                {{ $filas->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection

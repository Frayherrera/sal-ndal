@extends('layouts.app')

@section('title', 'Productos más Vendidos')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Productos más Vendidos</h1>
                <p class="text-white/60">Unidades e ingresos por producto.</p>
            </div>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('ventas.reportes.clientes') }}" class="text-sm text-blue-300 hover:text-blue-200 transition-colors">
                <i class="fas fa-users mr-1"></i> Ventas por cliente
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
                <a href="{{ route('ventas.reportes.productos') }}" class="text-white/50 hover:text-white text-sm py-3">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($filas->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-ranking-star text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay productos vendidos en el período seleccionado.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4 w-10">#</th>
                        <th class="p-4">Producto</th>
                        <th class="p-4 hidden sm:table-cell">Código</th>
                        <th class="p-4 text-right">Unidades</th>
                        <th class="p-4 text-right">Ingresos</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $indice => $fila)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4 text-white/40">{{ $filas->firstItem() + $indice }}</td>
                            <td class="p-4 text-white font-medium">{{ $fila->nombre }}</td>
                            <td class="p-4 text-white/40 font-mono hidden sm:table-cell">{{ $fila->codigo }}</td>
                            <td class="p-4 text-right text-white">{{ number_format($fila->unidades) }}</td>
                            <td class="p-4 text-right text-white font-semibold">${{ number_format($fila->ingresos, 0, ',', '.') }}</td>
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

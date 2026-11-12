@extends('layouts.app')

@section('title', 'Gastos por Categoría')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('gastos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Gastos por Categoría</h1>
                <p class="text-white/60">En qué se va el dinero en el periodo seleccionado.</p>
            </div>
        </div>
        <a href="{{ route('gastos.reportes.resultado') }}" class="text-sm text-purple-300 hover:text-purple-200 transition-colors">
            <i class="fas fa-scale-balanced mr-1"></i> Ventas − gastos
        </a>
    </div>

    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="grid sm:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs text-white/50 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ $desde }}" class="input-glass">
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}" class="input-glass">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 flex items-center justify-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
            </div>
        </form>
    </div>

    <div class="glass-card rounded-2xl p-5 mb-6 animate-delay-100">
        <div class="text-xs text-white/50 mb-1">Total gastado en el periodo</div>
        <div class="text-3xl font-bold text-red-300">${{ number_format($total, 0, ',', '.') }}</div>
    </div>

    <div class="glass-card rounded-2xl p-6 animate-delay-200">
        @if ($filas->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-chart-pie text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay gastos en este periodo.</p>
            </div>
        @else
            <div class="space-y-5">
                @foreach ($filas as $fila)
                    @php
                        $porcentaje = $total > 0 ? round((float) $fila->total / $total * 100, 1) : 0;
                    @endphp
                    <div>
                        <div class="flex items-baseline justify-between gap-4 mb-2">
                            <div>
                                <a href="{{ route('gastos.index', ['categoria_id' => $fila->categoria_gasto_id, 'desde' => $desde, 'hasta' => $hasta, 'estado' => 'registrado']) }}"
                                   class="text-white font-medium hover:text-blue-200">
                                    {{ $fila->categoria?->nombre ?? 'Sin categoría' }}
                                </a>
                                <span class="text-xs text-white/40 ml-2">{{ $fila->gastos_count }} {{ $fila->gastos_count === 1 ? 'gasto' : 'gastos' }}</span>
                            </div>
                            <div class="text-right whitespace-nowrap">
                                <span class="text-white font-semibold">${{ number_format($fila->total, 0, ',', '.') }}</span>
                                <span class="text-xs text-white/50 ml-2 w-12 inline-block">{{ number_format($porcentaje, 1, ',', '.') }}%</span>
                            </div>
                        </div>
                        <div class="h-2 rounded-full bg-white/5 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-blue-500 to-purple-600" style="width: {{ $porcentaje }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endsection

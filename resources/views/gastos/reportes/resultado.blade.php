@extends('layouts.app')

@section('title', 'Ventas − Gastos')

@section('content')
    @php
        $fmt = fn ($valor) => ($valor < 0 ? '−$' : '$').number_format(abs($valor), 0, ',', '.');
        $mesActual = now()->year === $anio ? now()->month : null;
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('gastos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Ventas − Gastos {{ $anio }}</h1>
                <p class="text-white/60">Utilidad aproximada por mes: lo vendido menos lo gastado.</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('gastos.reportes.resultado', ['anio' => $anio - 1]) }}"
               class="w-10 h-10 inline-flex items-center justify-center rounded-xl glass-card text-white/70 hover:text-white" title="Año anterior">
                <i class="fas fa-chevron-left"></i>
            </a>
            <span class="px-3 text-white font-semibold">{{ $anio }}</span>
            <a href="{{ route('gastos.reportes.resultado', ['anio' => $anio + 1]) }}"
               class="w-10 h-10 inline-flex items-center justify-center rounded-xl glass-card text-white/70 hover:text-white" title="Año siguiente">
                <i class="fas fa-chevron-right"></i>
            </a>
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Vendido</div>
            <div class="text-2xl font-bold text-white">{{ $fmt($totales['ventas']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Cobrado</div>
            <div class="text-2xl font-bold text-green-300">{{ $fmt($totales['cobrado']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-200">
            <div class="text-xs text-white/50 mb-1">Gastado</div>
            <div class="text-2xl font-bold text-red-300">{{ $fmt($totales['gastos']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-300">
            <div class="text-xs text-white/50 mb-1">Ventas − gastos</div>
            <div class="text-2xl font-bold {{ $totales['diferencia'] >= 0 ? 'text-blue-200' : 'text-red-300' }}">{{ $fmt($totales['diferencia']) }}</div>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-200 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-white/50 border-b border-white/10">
                    <th class="p-4">Mes</th>
                    <th class="p-4 text-right">Vendido</th>
                    <th class="p-4 text-right hidden sm:table-cell">Cobrado</th>
                    <th class="p-4 text-right">Gastado</th>
                    <th class="p-4 text-right">Ventas − gastos</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($meses as $fila)
                    @php
                        $vacio = $fila['ventas'] == 0 && $fila['gastos'] == 0 && $fila['cobrado'] == 0;
                    @endphp
                    <tr class="border-b border-white/5 {{ $fila['mes'] === $mesActual ? 'bg-blue-500/10' : 'hover:bg-white/5' }} {{ $vacio ? 'text-white/30' : '' }}">
                        <td class="p-4 font-medium {{ $vacio ? '' : 'text-white' }}">
                            {{ $fila['nombre'] }}
                            @if ($fila['mes'] === $mesActual)
                                <span class="ml-2 text-xs text-blue-300">actual</span>
                            @endif
                        </td>
                        <td class="p-4 text-right {{ $vacio ? '' : 'text-white' }}">{{ $fmt($fila['ventas']) }}</td>
                        <td class="p-4 text-right hidden sm:table-cell {{ $vacio ? '' : 'text-green-300' }}">{{ $fmt($fila['cobrado']) }}</td>
                        <td class="p-4 text-right {{ $vacio ? '' : 'text-red-300' }}">{{ $fmt($fila['gastos']) }}</td>
                        <td class="p-4 text-right font-semibold {{ $vacio ? '' : ($fila['diferencia'] >= 0 ? 'text-blue-200' : 'text-red-300') }}">
                            {{ $fmt($fila['diferencia']) }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-white/20">
                    <td class="p-4 text-white font-bold">Total {{ $anio }}</td>
                    <td class="p-4 text-right text-white font-bold">{{ $fmt($totales['ventas']) }}</td>
                    <td class="p-4 text-right text-green-300 font-bold hidden sm:table-cell">{{ $fmt($totales['cobrado']) }}</td>
                    <td class="p-4 text-right text-red-300 font-bold">{{ $fmt($totales['gastos']) }}</td>
                    <td class="p-4 text-right font-bold {{ $totales['diferencia'] >= 0 ? 'text-blue-200' : 'text-red-300' }}">{{ $fmt($totales['diferencia']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <p class="text-xs text-white/40 mt-4">
        <i class="fas fa-circle-info mr-1"></i>
        "Vendido" suma las ventas confirmadas por su fecha; "Cobrado" suma los abonos recibidos en el mes.
        Los gastos incluyen las compras de materia prima registradas con costo. No incluye ventas ni gastos anulados.
    </p>
@endsection

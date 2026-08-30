@extends('layouts.app')

@section('title', 'Alertas de Stock Bajo')

@section('content')
    <div class="mb-8 animate-fade-in">
        <h1 class="text-3xl font-bold text-white mb-2">Alertas de Stock Bajo</h1>
        <p class="text-white/60">Artículos que han alcanzado o superado su stock mínimo.</p>
    </div>

    @if ($materias_primas->isEmpty() && $productos->isEmpty())
        <div class="glass-card rounded-2xl p-16 text-center animate-delay-100">
            <div class="w-16 h-16 bg-green-500/20 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <i class="fas fa-check text-green-400 text-2xl"></i>
            </div>
            <h2 class="text-lg font-semibold text-white mb-1">Sin alertas</h2>
            <p class="text-white/50 text-sm">Todos los artículos tienen stock suficiente.</p>
        </div>
    @else
        @if (!$materias_primas->isEmpty())
            <div class="glass-card rounded-2xl p-6 mb-6 animate-delay-100">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-seedling text-blue-400"></i>
                    </div>
                    <h2 class="text-lg font-semibold text-white">Materias Primas</h2>
                    <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">{{ $materias_primas->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-white/50 border-b border-white/10">
                                <th class="py-2 pr-4">Nombre</th>
                                <th class="py-2 pr-4 text-right">Stock actual</th>
                                <th class="py-2 pr-4 text-right">Mínimo</th>
                                <th class="py-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($materias_primas as $mp)
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                    <td class="py-2.5 pr-4 text-white font-medium">{{ $mp->nombre }}</td>
                                    <td class="py-2.5 pr-4 text-right text-yellow-300 font-semibold">
                                        {{ number_format($mp->stock_kg(), $mp->unidad_base === 'g' ? 0 : 2) }} {{ $mp->unidad_base }}
                                    </td>
                                    <td class="py-2.5 pr-4 text-right text-white/60">{{ number_format($mp->stock_minimo, 2) }} {{ $mp->unidad_base }}</td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('inventario.movimientos.create', ['tipo' => 'compra_recepcion']) }}"
                                           class="text-blue-300 hover:text-blue-200 text-xs"><i class="fas fa-arrow-down"></i> Comprar</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (!$productos->isEmpty())
            <div class="glass-card rounded-2xl p-6 animate-delay-200">
                <div class="flex items-center gap-3 mb-4">
                    <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                        <i class="fas fa-box-open text-purple-400"></i>
                    </div>
                    <h2 class="text-lg font-semibold text-white">Productos Terminados</h2>
                    <span class="text-xs text-yellow-400 bg-yellow-400/10 px-2 py-1 rounded-full">{{ $productos->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-white/50 border-b border-white/10">
                                <th class="py-2 pr-4">Nombre</th>
                                <th class="py-2 pr-4 text-right">Disponibles</th>
                                <th class="py-2 pr-4 text-right">Mínimo</th>
                                <th class="py-2 text-right">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productos as $pt)
                                <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                                    <td class="py-2.5 pr-4 text-white font-medium">{{ $pt->nombre }}</td>
                                    <td class="py-2.5 pr-4 text-right text-yellow-300 font-semibold">{{ number_format($pt->stock_disponible()) }} u</td>
                                    <td class="py-2.5 pr-4 text-right text-white/60">{{ number_format($pt->stock_minimo, 0) }} u</td>
                                    <td class="py-2.5 text-right">
                                        <a href="{{ route('inventario.movimientos.create', ['tipo' => 'producto_producido']) }}"
                                           class="text-purple-300 hover:text-purple-200 text-xs"><i class="fas fa-box-open"></i> Producir</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
@endsection

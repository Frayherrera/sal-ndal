@extends('layouts.app')

@section('title', 'Camión')

@php use App\Support\Paquetes; use App\Models\ViajeCamion; @endphp

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Camión</h1>
            <p class="text-white/60">Cargue, viajes semanales por ruta y lo que se vende en cada uno.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('camion.rutas.index') }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-route"></i><span>Rutas</span>
            </a>
            <a href="{{ route('camion.reportes.semanal') }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-chart-column"></i><span>Vendido por semana</span>
            </a>
        </div>
    </div>

    <!-- Estado actual del camión -->
    @if ($enRuta)
        <div class="glass rounded-2xl p-6 mb-8 border border-blue-500/30 animate-delay-100">
            <div class="flex flex-col md:flex-row md:items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-truck-fast text-blue-300 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <div class="text-white text-lg font-semibold">El camión está en ruta: {{ $enRuta->ruta?->nombre }}</div>
                    <div class="text-white/60 text-sm">
                        <span class="font-mono">{{ $enRuta->numero }}</span> · salió el {{ $enRuta->fecha_salida->format('d/m/Y') }}
                        · lleva {{ Paquetes::texto($enRuta->detalles->sum('cargado')) }} en {{ $enRuta->detalles->where('cargado', '>', 0)->count() }} productos
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a href="{{ route('camion.show', $enRuta) }}" class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white">
                        <i class="fas fa-eye"></i> Ver carga
                    </a>
                    <a href="{{ route('camion.cerrar', $enRuta) }}"
                       class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-5 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-flag-checkered"></i> Registrar regreso
                    </a>
                </div>
            </div>
        </div>
    @elseif (! $hayRutas)
        <div class="glass rounded-2xl p-8 mb-8 text-center animate-delay-100">
            <i class="fas fa-route text-4xl text-white/20 mb-4"></i>
            <p class="text-white/70 mb-4">Para empezar, crea las rutas fijas que hace el camión y su orden en la rotación.</p>
            <a href="{{ route('camion.rutas.create') }}"
               class="inline-flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-5 py-2.5 rounded-xl font-semibold shadow-lg shadow-blue-500/30">
                <i class="fas fa-plus"></i> Crear la primera ruta
            </a>
        </div>
    @else
        <div class="glass rounded-2xl p-6 mb-8 animate-delay-100">
            <div class="flex flex-col md:flex-row md:items-center gap-5">
                <div class="w-14 h-14 rounded-2xl bg-green-500/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-warehouse text-green-300 text-2xl"></i>
                </div>
                <div class="flex-1">
                    <div class="text-white text-lg font-semibold">El camión está en casa</div>
                    <div class="text-white/60 text-sm">
                        @if ($unidadesEnCamion > 0)
                            Tiene {{ Paquetes::texto($unidadesEnCamion) }} en {{ $productosEnCamion }} productos que regresaron del último viaje.
                        @else
                            Está vacío.
                        @endif
                        @if ($rutaSugerida)
                            Siguiente ruta en la rotación: <span class="text-white">{{ $rutaSugerida->nombre }}</span>.
                        @endif
                    </div>
                </div>
                <a href="{{ route('camion.create') }}"
                   class="flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-5 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                    <i class="fas fa-truck-ramp-box"></i> Surtir y cargar el camión
                </a>
            </div>
        </div>
    @endif

    <!-- Historial de viajes -->
    <div class="glass-card rounded-2xl p-2 animate-delay-200 overflow-x-auto">
        @if ($viajes->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-truck text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">Todavía no hay viajes registrados.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Viaje</th>
                        <th class="p-4">Ruta</th>
                        <th class="p-4 hidden md:table-cell">Salida</th>
                        <th class="p-4 hidden md:table-cell">Regreso</th>
                        <th class="p-4 text-right hidden sm:table-cell">Salió</th>
                        <th class="p-4 text-right">Vendido</th>
                        <th class="p-4 text-center">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($viajes as $viaje)
                        @php
                            $badge = [
                                ViajeCamion::EN_RUTA => 'bg-blue-500/20 text-blue-300',
                                ViajeCamion::CERRADO => 'bg-green-500/20 text-green-300',
                                ViajeCamion::ANULADO => 'bg-white/10 text-white/50',
                            ][$viaje->estado] ?? 'bg-white/10 text-white/60';
                        @endphp
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors {{ $viaje->estado === ViajeCamion::ANULADO ? 'opacity-60' : '' }}">
                            <td class="p-4">
                                <a href="{{ route('camion.show', $viaje) }}" class="font-mono text-blue-300 hover:text-blue-200">{{ $viaje->numero }}</a>
                            </td>
                            <td class="p-4 text-white">{{ $viaje->ruta?->nombre }}</td>
                            <td class="p-4 text-white/60 hidden md:table-cell">{{ $viaje->fecha_salida->format('d/m/Y') }}</td>
                            <td class="p-4 text-white/60 hidden md:table-cell">{{ $viaje->fecha_regreso?->format('d/m/Y') ?? '—' }}</td>
                            <td class="p-4 text-right text-white/70 hidden sm:table-cell whitespace-nowrap">{{ Paquetes::texto((int) $viaje->detalles_sum_cargado) }}</td>
                            <td class="p-4 text-right text-white font-medium whitespace-nowrap">
                                {{ $viaje->estado === ViajeCamion::CERRADO ? Paquetes::texto((int) $viaje->detalles_sum_vendido) : '—' }}
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs whitespace-nowrap {{ $badge }}">{{ ViajeCamion::ESTADOS[$viaje->estado] ?? $viaje->estado }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            <div class="p-4">{{ $viajes->links() }}</div>
        @endif
    </div>

    <p class="text-xs text-white/40 mt-4">
        <i class="fas fa-circle-info mr-1"></i>
        Las cantidades se muestran en paquetes de {{ Paquetes::UNIDADES }} (p) y unidades sueltas (u).
    </p>
@endsection

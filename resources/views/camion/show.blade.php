@extends('layouts.app')

@section('title', 'Viaje '.$viaje->numero)

@php use App\Support\Paquetes; use App\Models\ViajeCamion; @endphp

@section('content')
    @php
        $dinero = fn ($v) => ($v < 0 ? '−$' : '$').number_format(abs($v), 0, ',', '.');
        $badge = [
            ViajeCamion::EN_RUTA => 'bg-blue-500/20 text-blue-300',
            ViajeCamion::CERRADO => 'bg-green-500/20 text-green-300',
            ViajeCamion::ANULADO => 'bg-white/10 text-white/50',
        ][$viaje->estado] ?? 'bg-white/10 text-white/60';
        $cerrado = $viaje->estaCerrado();
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('camion.index') }}" class="text-white/60 hover:text-white transition-colors"><i class="fas fa-arrow-left"></i></a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-white font-mono">{{ $viaje->numero }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs {{ $badge }}">{{ ViajeCamion::ESTADOS[$viaje->estado] ?? $viaje->estado }}</span>
                </div>
                <p class="text-white/50 text-sm">
                    {{ $viaje->ruta?->nombre }} · salió el {{ $viaje->fecha_salida->format('d/m/Y') }}
                    @if ($viaje->fecha_regreso) · regresó el {{ $viaje->fecha_regreso->format('d/m/Y') }} @endif
                </p>
            </div>
        </div>

        @if ($viaje->estaEnRuta())
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('camion.cerrar', $viaje) }}"
                   class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-5 py-2.5 rounded-xl font-semibold shadow-lg shadow-blue-500/30">
                    <i class="fas fa-flag-checkered"></i> Registrar regreso
                </a>
                <form method="POST" action="{{ route('camion.anular', $viaje) }}"
                      onsubmit="const m = prompt('Motivo de la anulación (el surtido vuelve a bodega):'); if (!m) return false; this.motivo.value = m; return true;">
                    @csrf
                    <input type="hidden" name="motivo">
                    <button type="submit" class="flex items-center gap-2 bg-red-500/20 text-red-300 px-4 py-2.5 rounded-xl font-medium hover:bg-red-500/30">
                        <i class="fas fa-ban"></i> Anular viaje
                    </button>
                </form>
            </div>
        @endif
    </div>

    @if ($viaje->estado === ViajeCamion::ANULADO)
        <div class="glass-card rounded-2xl p-5 mb-6 border border-red-500/20 text-sm">
            <span class="text-red-300 font-medium">Viaje anulado:</span>
            <span class="text-white/60">{{ $viaje->motivo_anulacion }}</span>
        </div>
    @endif

    @if ($resultado)
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="glass-card rounded-2xl p-5">
                <div class="text-xs text-white/50 mb-1">Vendido según el camión</div>
                <div class="text-2xl font-bold text-white">{{ $dinero($resultado['vendido']) }}</div>
                <div class="text-xs text-white/40">{{ Paquetes::texto($viaje->detalles->sum('vendido')) }} a precio de catálogo</div>
            </div>
            <div class="glass-card rounded-2xl p-5">
                <div class="text-xs text-white/50 mb-1">Ventas registradas</div>
                <div class="text-2xl font-bold text-white">{{ $dinero($resultado['ventas_registradas']) }}</div>
                <div class="text-xs text-white/40">
                    {{ $resultado['ventas_count'] }} ventas {{ $resultado['filtra_por_ciudad'] ? 'a clientes de la ruta' : 'en esas fechas' }}
                </div>
            </div>
            <div class="glass-card rounded-2xl p-5">
                <div class="text-xs text-white/50 mb-1">Gastos de ruta</div>
                <div class="text-2xl font-bold text-red-300">{{ $dinero($resultado['gastos_ruta']) }}</div>
                <div class="text-xs text-white/40">Categoría «Ruta» en esas fechas</div>
            </div>
            <div class="glass-card rounded-2xl p-5">
                <div class="text-xs text-white/50 mb-1">Ventas − gastos</div>
                <div class="text-2xl font-bold {{ $resultado['resultado'] >= 0 ? 'text-blue-200' : 'text-red-300' }}">{{ $dinero($resultado['resultado']) }}</div>
                <div class="text-xs text-white/40">Merma: {{ $dinero($resultado['merma']) }}</div>
            </div>
        </div>

        @php $dif = $resultado['diferencia']; @endphp
        <div class="glass-card rounded-2xl p-4 mb-6 text-sm {{ abs($dif) < 1 ? 'border border-green-500/20' : 'border border-yellow-500/30' }}">
            <i class="fas {{ abs($dif) < 1 ? 'fa-circle-check text-green-300' : 'fa-scale-unbalanced text-yellow-300' }} mr-1"></i>
            @if (abs($dif) < 1)
                Lo registrado como ventas coincide con lo que salió del camión.
            @else
                Diferencia entre ventas registradas y lo vendido según el camión: <b class="text-white">{{ $dinero($dif) }}</b>.
                <span class="text-white/50">Puede deberse a precios distintos al catálogo, descuentos o ventas sin registrar.</span>
            @endif
        </div>
    @endif

    <div class="glass-card rounded-2xl p-2 overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-white/50 border-b border-white/10">
                    <th class="p-3">Producto</th>
                    <th class="p-3 text-right hidden lg:table-cell">Venía</th>
                    <th class="p-3 text-right hidden lg:table-cell">Surtido</th>
                    <th class="p-3 text-right hidden lg:table-cell">Bajado</th>
                    <th class="p-3 text-right">Salió</th>
                    @if ($cerrado)
                        <th class="p-3 text-right">Regresó</th>
                        <th class="p-3 text-right hidden sm:table-cell">Dañado</th>
                        <th class="p-3 text-right hidden sm:table-cell">Regalado</th>
                        <th class="p-3 text-right">Vendido</th>
                        <th class="p-3 text-right hidden md:table-cell">Valor</th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @foreach ($detalles as $d)
                    <tr class="border-b border-white/5 hover:bg-white/5">
                        <td class="p-3">
                            <div class="text-white font-medium">{{ $d->producto?->nombreCompleto() }}</div>
                            <div class="text-xs text-white/40">{{ $d->producto?->presentacion }}</div>
                        </td>
                        <td class="p-3 text-right text-white/60 whitespace-nowrap hidden lg:table-cell">{{ Paquetes::texto($d->inicial) }}</td>
                        <td class="p-3 text-right text-white/60 whitespace-nowrap hidden lg:table-cell">{{ Paquetes::texto($d->surtido) }}</td>
                        <td class="p-3 text-right text-white/60 whitespace-nowrap hidden lg:table-cell">{{ Paquetes::texto($d->bajado) }}</td>
                        <td class="p-3 text-right text-white whitespace-nowrap">{{ Paquetes::texto($d->cargado) }}</td>
                        @if ($cerrado)
                            <td class="p-3 text-right text-white/70 whitespace-nowrap">{{ Paquetes::texto($d->regresado) }}</td>
                            <td class="p-3 text-right whitespace-nowrap hidden sm:table-cell {{ $d->danado ? 'text-red-300' : 'text-white/40' }}">{{ Paquetes::texto($d->danado) }}</td>
                            <td class="p-3 text-right whitespace-nowrap hidden sm:table-cell {{ $d->regalado ? 'text-yellow-300' : 'text-white/40' }}">{{ Paquetes::texto($d->regalado) }}</td>
                            <td class="p-3 text-right text-white font-semibold whitespace-nowrap">{{ Paquetes::texto($d->vendido) }}</td>
                            <td class="p-3 text-right text-white/70 whitespace-nowrap hidden md:table-cell">{{ $dinero($d->valorVendido()) }}</td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-white/20 font-semibold">
                    <td class="p-3 text-white">Total</td>
                    <td class="p-3 text-right text-white/70 hidden lg:table-cell">{{ Paquetes::texto($detalles->sum('inicial')) }}</td>
                    <td class="p-3 text-right text-white/70 hidden lg:table-cell">{{ Paquetes::texto($detalles->sum('surtido')) }}</td>
                    <td class="p-3 text-right text-white/70 hidden lg:table-cell">{{ Paquetes::texto($detalles->sum('bajado')) }}</td>
                    <td class="p-3 text-right text-white">{{ Paquetes::texto($detalles->sum('cargado')) }}</td>
                    @if ($cerrado)
                        <td class="p-3 text-right text-white/70">{{ Paquetes::texto($detalles->sum('regresado')) }}</td>
                        <td class="p-3 text-right text-red-300 hidden sm:table-cell">{{ Paquetes::texto($detalles->sum('danado')) }}</td>
                        <td class="p-3 text-right text-yellow-300 hidden sm:table-cell">{{ Paquetes::texto($detalles->sum('regalado')) }}</td>
                        <td class="p-3 text-right text-white">{{ Paquetes::texto($detalles->sum('vendido')) }}</td>
                        <td class="p-3 text-right text-white hidden md:table-cell">{{ $dinero($resultado['vendido'] ?? 0) }}</td>
                    @endif
                </tr>
            </tfoot>
        </table>
    </div>

    @if ($viaje->observaciones)
        <div class="glass-card rounded-2xl p-5 mt-6 text-sm text-white/60 whitespace-pre-line">{{ $viaje->observaciones }}</div>
    @endif

    <p class="text-xs text-white/40 mt-4">
        <i class="fas fa-circle-info mr-1"></i>
        p = paquetes de {{ Paquetes::UNIDADES }}, u = unidades sueltas. El surtido y lo bajado quedan en el kardex con el documento {{ $viaje->numero }}.
    </p>
@endsection

@extends('layouts.app')

@section('title', 'Vendido por semana')

@php use App\Support\Paquetes; @endphp

@section('content')
    @php $dinero = fn ($v) => '$'.number_format($v, 0, ',', '.'); @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('camion.index') }}" class="text-white/60 hover:text-white transition-colors"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Vendido por semana</h1>
                <p class="text-white/60">Lo que salió del camión menos lo que regresó, por producto y por viaje.</p>
            </div>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="grid sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-xs text-white/50 mb-1">Ruta</label>
                <select name="ruta_id" class="input-glass bg-gray-900/60">
                    <option value="">Todas</option>
                    @foreach ($rutas as $r)
                        <option value="{{ $r->id }}" @selected($rutaId === $r->id)>{{ $r->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Salidas desde</label>
                <input type="date" name="desde" value="{{ $desde }}" class="input-glass">
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ $hasta }}" class="input-glass">
            </div>
            <button type="submit" class="flex items-center justify-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                <i class="fas fa-filter"></i> Filtrar
            </button>
        </form>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Viajes cerrados</div>
            <div class="text-2xl font-bold text-white">{{ $viajes->count() }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Vendido</div>
            <div class="text-2xl font-bold text-white">{{ Paquetes::texto($granTotal['vendido']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Valor a precio de catálogo</div>
            <div class="text-2xl font-bold text-green-300">{{ $dinero($granTotal['valor']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5">
            <div class="text-xs text-white/50 mb-1">Merma (dañado + regalado)</div>
            <div class="text-2xl font-bold text-red-300">{{ $dinero($granTotal['merma_valor']) }}</div>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-2 overflow-x-auto animate-delay-200">
        @if ($viajes->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-chart-column text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay viajes cerrados en este periodo.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10 align-bottom">
                        <th class="p-3 min-w-[180px] sticky left-0 bg-gray-900/80">Producto</th>
                        @foreach ($viajes as $v)
                            <th class="p-3 text-right whitespace-nowrap">
                                <a href="{{ route('camion.show', $v) }}" class="text-blue-300 hover:text-blue-200 font-mono">{{ $v->numero }}</a>
                                <div class="text-xs text-white/60 font-normal">{{ $v->ruta?->nombre }}</div>
                                <div class="text-xs text-white/40 font-normal">{{ $v->fecha_salida->format('d/m') }}</div>
                            </th>
                        @endforeach
                        <th class="p-3 text-right">Total</th>
                        <th class="p-3 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($filas as $fila)
                        <tr class="border-b border-white/5 hover:bg-white/5">
                            <td class="p-3 sticky left-0 bg-gray-900/80">
                                <div class="text-white font-medium">{{ $fila['producto']?->nombre }}</div>
                                @if ($fila['merma'] > 0)
                                    <div class="text-xs text-red-300/80">merma {{ Paquetes::texto($fila['merma']) }}</div>
                                @endif
                            </td>
                            @foreach ($viajes as $v)
                                @php $u = $fila['por_viaje'][$v->id] ?? null; @endphp
                                <td class="p-3 text-right whitespace-nowrap {{ $u ? 'text-white/80' : 'text-white/25' }}">{{ $u === null ? '—' : Paquetes::texto($u) }}</td>
                            @endforeach
                            <td class="p-3 text-right text-white font-semibold whitespace-nowrap">{{ Paquetes::texto($fila['vendido']) }}</td>
                            <td class="p-3 text-right text-green-300 whitespace-nowrap">{{ $dinero($fila['valor']) }}</td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr class="border-t border-white/20 font-semibold">
                        <td class="p-3 text-white sticky left-0 bg-gray-900/80">Total vendido</td>
                        @foreach ($viajes as $v)
                            <td class="p-3 text-right whitespace-nowrap">
                                <div class="text-white">{{ Paquetes::texto($totales[$v->id]['vendido']) }}</div>
                                <div class="text-xs text-green-300">{{ $dinero($totales[$v->id]['valor']) }}</div>
                            </td>
                        @endforeach
                        <td class="p-3 text-right text-white">{{ Paquetes::texto($granTotal['vendido']) }}</td>
                        <td class="p-3 text-right text-green-300">{{ $dinero($granTotal['valor']) }}</td>
                    </tr>
                    <tr class="text-xs">
                        <td class="p-3 text-white/50 sticky left-0 bg-gray-900/80">Dañado / regalado</td>
                        @foreach ($viajes as $v)
                            <td class="p-3 text-right whitespace-nowrap text-white/50">
                                <span class="text-red-300/80">{{ Paquetes::texto($totales[$v->id]['danado']) }}</span> /
                                <span class="text-yellow-300/80">{{ Paquetes::texto($totales[$v->id]['regalado']) }}</span>
                            </td>
                        @endforeach
                        <td class="p-3" colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        @endif
    </div>

    <p class="text-xs text-white/40 mt-4">
        <i class="fas fa-circle-info mr-1"></i>
        p = paquetes de {{ Paquetes::UNIDADES }}, u = unidades sueltas. El valor usa el precio de catálogo del día en que se cargó el camión.
    </p>
@endsection

@extends('layouts.app')

@section('title', 'Cartera')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-white mb-1">Cartera</h1>
                <p class="text-white/60">Ventas con saldo pendiente y abonos.</p>
            </div>
        </div>
        <a href="{{ route('ventas.create') }}"
           class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
            <i class="fas fa-plus"></i> Nueva Venta
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
        <div class="glass-card rounded-2xl p-5 animate-fade-in">
            <div class="text-xs text-white/50 mb-1">Saldo total en cartera</div>
            <div class="text-3xl font-bold text-yellow-300">${{ number_format($resumen['total'], 0, ',', '.') }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Ventas con saldo</div>
            <div class="text-3xl font-bold text-white">{{ number_format($resumen['count']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-200">
            <div class="text-xs text-white/50 mb-1">Vencidas</div>
            <div class="text-3xl font-bold {{ $resumen['vencidas'] > 0 ? 'text-red-300' : 'text-white/40' }}">
                {{ number_format($resumen['vencidas']) }}
            </div>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="flex flex-wrap items-end gap-4">
            <div class="min-w-[220px]">
                <label class="block text-xs text-white/50 mb-1">Cliente</label>
                <select name="cliente_id" class="input-glass bg-gray-900/60">
                    <option value="">Todos</option>
                    @foreach ($clientes as $cliente)
                        <option value="{{ $cliente->id }}" @selected((string) request('cliente_id') === (string) $cliente->id)>
                            {{ $cliente->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
            <label class="flex items-center gap-2 text-sm text-white/70 py-3">
                <input type="checkbox" name="vencidas" value="1" @checked(request()->boolean('vencidas'))
                       class="rounded border-white/30 bg-white/10 text-blue-500 focus:ring-blue-500">
                Solo vencidas
            </label>
            <button type="submit" class="flex items-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                <i class="fas fa-filter"></i> Filtrar
            </button>
            @if (request()->hasAny(['cliente_id', 'vencidas']))
                <a href="{{ route('ventas.cartera') }}" class="text-white/50 hover:text-white text-sm py-3">Limpiar</a>
            @endif
        </form>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($ventas->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-wallet text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay saldos pendientes con estos filtros.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Venta</th>
                        <th class="p-4 hidden md:table-cell">Fecha</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4 hidden sm:table-cell">Vence</th>
                        <th class="p-4 text-right">Total</th>
                        <th class="p-4 text-right hidden sm:table-cell">Pagado</th>
                        <th class="p-4 text-right">Saldo</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventas as $venta)
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="p-4">
                                <a href="{{ route('ventas.show', $venta) }}" class="font-mono text-blue-300 hover:text-blue-200">
                                    {{ $venta->numero }}
                                </a>
                            </td>
                            <td class="p-4 text-white/60 whitespace-nowrap hidden md:table-cell">{{ $venta->fecha->format('d/m/Y') }}</td>
                            <td class="p-4">
                                <div class="text-white font-medium">{{ $venta->cliente?->nombre ?? '—' }}</div>
                                @if ($venta->cliente?->local)
                                    <div class="text-xs text-white/40">{{ $venta->cliente->local }}</div>
                                @endif
                            </td>
                            <td class="p-4 hidden sm:table-cell">
                                @if ($venta->fecha_vencimiento)
                                    <span class="{{ $venta->estaVencida() ? 'text-red-300 font-medium' : 'text-white/60' }}">
                                        {{ $venta->fecha_vencimiento->format('d/m/Y') }}
                                    </span>
                                @else
                                    <span class="text-white/30">—</span>
                                @endif
                            </td>
                            <td class="p-4 text-right text-white">${{ number_format($venta->total, 0, ',', '.') }}</td>
                            <td class="p-4 text-right text-green-300 hidden sm:table-cell">${{ number_format($venta->totalPagado(), 0, ',', '.') }}</td>
                            <td class="p-4 text-right font-semibold {{ $venta->estaVencida() ? 'text-red-300' : 'text-yellow-300' }}">
                                ${{ number_format($venta->saldo(), 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-right">
                                <a href="{{ route('ventas.abonos.create', $venta) }}"
                                   class="inline-flex items-center gap-1 text-green-300 hover:text-green-200 text-xs">
                                    <i class="fas fa-hand-holding-dollar"></i> Abonar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="p-4">
                {{ $ventas->withQueryString()->links() }}
            </div>
        @endif
    </div>
@endsection

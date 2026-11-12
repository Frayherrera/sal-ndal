@extends('layouts.app')

@section('title', 'Ventas')

@section('content')
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div>
            <h1 class="text-3xl font-bold text-white mb-2">Ventas</h1>
            <p class="text-white/60">Ventas por valor total, pagos y cartera.</p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            <a href="{{ route('ventas.cartera') }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-wallet"></i>
                <span>Cartera</span>
            </a>
            <a href="{{ route('ventas.export', request()->query()) }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-file-csv"></i>
                <span>CSV</span>
            </a>
            <a href="{{ route('ventas.create') }}"
               class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-4 py-2.5 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                <i class="fas fa-plus"></i>
                <span>Nueva Venta</span>
            </a>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3 mb-6 animate-delay-100">
        <a href="{{ route('ventas.reportes.clientes') }}"
           class="flex items-center gap-2 text-sm text-blue-300 hover:text-blue-200 transition-colors">
            <i class="fas fa-users"></i> Ventas por cliente
        </a>
    </div>

    <!-- Filters -->
    <div class="glass-card rounded-2xl p-4 mb-6 animate-delay-100">
        <form method="GET" class="grid sm:grid-cols-2 lg:grid-cols-6 gap-4 items-end">
            <div>
                <label class="block text-xs text-white/50 mb-1">Desde</label>
                <input type="date" name="desde" value="{{ request('desde') }}" class="input-glass">
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Hasta</label>
                <input type="date" name="hasta" value="{{ request('hasta') }}" class="input-glass">
            </div>
            <div>
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
            <div>
                <label class="block text-xs text-white/50 mb-1">Estado</label>
                <select name="estado" class="input-glass bg-gray-900/60">
                    <option value="">Todos</option>
                    @foreach ($estados as $key => $label)
                        <option value="{{ $key }}" @selected(request('estado') === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-white/50 mb-1">Buscar</label>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Número o cliente..." class="input-glass">
            </div>
            <div class="flex items-center gap-2">
                <button type="submit" class="flex-1 flex items-center justify-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-3 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                @if (request()->hasAny(['desde', 'hasta', 'cliente_id', 'estado', 'q']))
                    <a href="{{ route('ventas.index') }}" class="text-white/50 hover:text-white text-sm py-3 px-1">Limpiar</a>
                @endif
            </div>
        </form>
    </div>

    <!-- Summary -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Ventas filtradas</div>
            <div class="text-2xl font-bold text-white">{{ number_format($resumen['count']) }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Total vendido</div>
            <div class="text-2xl font-bold text-white">${{ number_format($resumen['total'], 0, ',', '.') }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-200">
            <div class="text-xs text-white/50 mb-1">Total abonado</div>
            <div class="text-2xl font-bold text-green-300">${{ number_format($resumen['pagado'], 0, ',', '.') }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-300">
            <div class="text-xs text-white/50 mb-1">Saldo pendiente</div>
            <div class="text-2xl font-bold text-yellow-300">${{ number_format($resumen['saldo'], 0, ',', '.') }}</div>
        </div>
    </div>

    <div class="glass-card rounded-2xl p-2 animate-delay-100 overflow-x-auto">
        @if ($ventas->isEmpty())
            <div class="text-center py-16">
                <i class="fas fa-cash-register text-4xl text-white/20 mb-4"></i>
                <p class="text-white/60">No hay ventas registradas con estos filtros.</p>
                <a href="{{ route('ventas.create') }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                    Registrar la primera venta →</a>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="p-4">Número</th>
                        <th class="p-4 hidden md:table-cell">Fecha</th>
                        <th class="p-4">Cliente</th>
                        <th class="p-4 text-right">Total</th>
                        <th class="p-4 text-right hidden sm:table-cell">Pagado</th>
                        <th class="p-4 text-right">Saldo</th>
                        <th class="p-4 text-center">Estado</th>
                        <th class="p-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($ventas as $venta)
                        @php
                            $estadoPago = $venta->estadoPago();
                            $badge = [
                                'pagada' => 'bg-green-500/20 text-green-300',
                                'pendiente' => 'bg-yellow-500/20 text-yellow-300',
                                'vencida' => 'bg-red-500/20 text-red-300',
                                'anulada' => 'bg-white/10 text-white/50',
                            ][$estadoPago] ?? 'bg-white/10 text-white/60';
                        @endphp
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
                            <td class="p-4 text-right text-white font-medium">${{ number_format($venta->total, 0, ',', '.') }}</td>
                            <td class="p-4 text-right text-green-300 hidden sm:table-cell">${{ number_format($venta->totalPagado(), 0, ',', '.') }}</td>
                            <td class="p-4 text-right {{ $venta->saldo() > 0 ? 'text-yellow-300' : 'text-white/40' }}">
                                ${{ number_format($venta->saldo(), 0, ',', '.') }}
                            </td>
                            <td class="p-4 text-center">
                                <span class="px-2 py-0.5 rounded-full text-xs whitespace-nowrap {{ $badge }}">
                                    {{ $estados[$estadoPago] ?? $estadoPago }}
                                </span>
                            </td>
                            <td class="p-4">
                                <div class="flex items-center justify-end gap-2">
                                    @if ($venta->estado === \App\Models\Venta::ESTADO_CONFIRMADA && $venta->saldo() > 0)
                                        <a href="{{ route('ventas.abonos.create', $venta) }}"
                                           class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-green-300 hover:text-green-200"
                                           title="Registrar abono">
                                            <i class="fas fa-hand-holding-dollar text-sm"></i>
                                        </a>
                                    @endif
                                    <a href="{{ route('ventas.show', $venta) }}"
                                       class="w-9 h-9 inline-flex items-center justify-center rounded-lg glass text-white/60 hover:text-white"
                                       title="Ver detalle">
                                        <i class="fas fa-eye text-sm"></i>
                                    </a>
                                </div>
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

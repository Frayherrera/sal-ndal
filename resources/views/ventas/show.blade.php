@extends('layouts.app')

@section('title', 'Venta '.$venta->numero)

@section('content')
    @php
        $estadoPago = $venta->estadoPago();
        $badge = [
            'pagada' => 'bg-green-500/20 text-green-300',
            'pendiente' => 'bg-yellow-500/20 text-yellow-300',
            'vencida' => 'bg-red-500/20 text-red-300',
            'anulada' => 'bg-white/10 text-white/50',
        ][$estadoPago] ?? 'bg-white/10 text-white/60';
        $saldo = $venta->saldo();
    @endphp

    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-8 animate-fade-in">
        <div class="flex items-center gap-3">
            <a href="{{ route('ventas.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="flex items-center gap-3">
                    <h1 class="text-2xl font-bold text-white font-mono">{{ $venta->numero }}</h1>
                    <span class="px-2.5 py-0.5 rounded-full text-xs {{ $badge }}">
                        {{ \App\Models\Venta::ESTADO_PAGO_LABELS[$estadoPago] ?? $estadoPago }}
                    </span>
                </div>
                <p class="text-white/50 text-sm">
                    {{ $venta->fecha->format('d/m/Y') }} · {{ $venta->cliente?->nombre }}
                    @if ($venta->user)
                        · Registrada por {{ $venta->user->name }}
                    @endif
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('ventas.recibo', $venta) }}"
               class="flex items-center gap-2 glass-card text-white/70 px-4 py-2.5 rounded-xl font-medium hover:text-white transition-colors">
                <i class="fas fa-print"></i> Recibo
            </a>

            @if ($venta->estado === \App\Models\Venta::ESTADO_CONFIRMADA && $saldo > 0)
                <a href="{{ route('ventas.abonos.create', $venta) }}"
                   class="flex items-center gap-2 bg-green-500/20 text-green-300 px-4 py-2.5 rounded-xl font-medium hover:bg-green-500/30 transition-colors">
                    <i class="fas fa-hand-holding-dollar"></i> Registrar abono
                </a>
            @endif

            @if ($venta->formaPago() === 'Contado')
                <span class="flex items-center gap-2 bg-green-500/20 text-green-300 px-4 py-2.5 rounded-xl font-medium">
                    <i class="fas fa-check-circle mr-1"></i> Contado
                </span>
            @elseif ($venta->formaPago() === 'Crédito')
                <span class="flex items-center gap-2 bg-yellow-500/20 text-yellow-300 px-4 py-2.5 rounded-xl font-medium">
                    <i class="fas fa-credit-card mr-1"></i> Crédito
                </span>
            @endif
        </div>
    </div>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Total</div>
            <div class="text-2xl font-bold text-white">${{ number_format($venta->total, 0, ',', '.') }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-100">
            <div class="text-xs text-white/50 mb-1">Abonado</div>
            <div class="text-2xl font-bold text-green-300">${{ number_format($venta->totalPagado(), 0, ',', '.') }}</div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-200">
            <div class="text-xs text-white/50 mb-1">Saldo</div>
            <div class="text-2xl font-bold {{ $saldo > 0 ? 'text-yellow-300' : 'text-white/40' }}">
                ${{ number_format($saldo, 0, ',', '.') }}
            </div>
        </div>
        <div class="glass-card rounded-2xl p-5 animate-delay-300">
            <div class="text-xs text-white/50 mb-1">Vencimiento</div>
            <div class="text-2xl font-bold {{ $venta->estaVencida() ? 'text-red-300' : 'text-white' }}">
                {{ $venta->fecha_vencimiento?->format('d/m/Y') ?? '—' }}
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            @if ($venta->detalles->isEmpty())
            <div class="glass-card rounded-2xl p-6 animate-fade-in">
                <h2 class="text-lg font-semibold text-white mb-4">Detalle</h2>
                <div class="flex items-center justify-between p-4 rounded-xl bg-white/5">
                    <div>
                        <div class="text-white font-medium">Venta por valor total</div>
                        <div class="text-sm text-white/50 whitespace-pre-line">{{ $venta->observaciones ?: 'Sin descripción' }}</div>
                    </div>
                    <div class="text-2xl font-bold text-white whitespace-nowrap ml-4">${{ number_format($venta->total, 0, ',', '.') }}</div>
                </div>
            </div>
            @else
            <div class="glass-card rounded-2xl p-6 animate-fade-in">
                <h2 class="text-lg font-semibold text-white mb-1">Productos</h2>
                <p class="text-xs text-white/40 mb-4">Venta registrada por productos (anterior al cambio a valor total).</p>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="text-left text-white/50 border-b border-white/10">
                                <th class="p-3">Producto</th>
                                <th class="p-3 text-right">Unidades</th>
                                <th class="p-3 text-right hidden md:table-cell">Precio unit.</th>
                                <th class="p-3 text-right hidden sm:table-cell">Descuento</th>
                                <th class="p-3 text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($venta->detalles as $detalle)
                                <tr class="border-b border-white/5">
                                    <td class="p-3">
                                        <div class="text-white font-medium">{{ $detalle->producto?->nombre ?? 'Producto eliminado' }}</div>
                                        <div class="text-xs text-white/40 font-mono">{{ $detalle->producto?->codigo }}</div>
                                    </td>
                                    <td class="p-3 text-right text-white">{{ $detalle->cantidad_unidades }}</td>
                                    <td class="p-3 text-right text-white/60 hidden md:table-cell">${{ number_format($detalle->precio_unidad, 0, ',', '.') }}</td>
                                    <td class="p-3 text-right text-red-300 hidden sm:table-cell">
                                        @if ((float) $detalle->descuento_monto > 0)
                                            @if ($detalle->descuento_tipo === 'porcentaje')
                                                {{ rtrim(rtrim(number_format($detalle->descuento_valor, 2, ',', '.'), '0'), ',') }}%
                                            @else
                                                ${{ number_format($detalle->descuento_monto, 0, ',', '.') }}
                                            @endif
                                        @else
                                            <span class="text-white/30">—</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right text-white font-medium">${{ number_format($detalle->total, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 ml-auto max-w-xs space-y-2 text-sm">
                    <div class="flex justify-between">
                        <span class="text-white/60">Subtotal</span>
                        <span class="text-white">{{ number_format($venta->subtotal, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-white/60">Descuento</span>
                        <span class="text-red-300">−${{ number_format($venta->descuento_total, 0, ',', '.') }}</span>
                    </div>
                    <div class="flex justify-between border-t border-white/10 pt-2">
                        <span class="text-white font-semibold">Total</span>
                        <span class="text-white font-bold">{{ number_format($venta->total, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            @endif

            <div class="glass-card rounded-2xl p-6 animate-fade-in">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-white">Abonos</h2>
                    @if ($venta->estado === \App\Models\Venta::ESTADO_CONFIRMADA && $saldo > 0)
                        <a href="{{ route('ventas.abonos.create', $venta) }}" class="text-sm text-green-300 hover:text-green-200">
                            <i class="fas fa-plus mr-1"></i> Registrar
                        </a>
                    @endif
                </div>

                @if ($venta->pagos->isEmpty())
                    <p class="text-white/40 text-sm py-4">No hay abonos registrados.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($venta->pagos->sortByDesc('fecha') as $pago)
                            <div class="flex items-center gap-3 p-3 rounded-xl bg-white/5">
                                <div class="w-9 h-9 rounded-lg bg-green-500/20 flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-hand-holding-dollar text-green-400 text-sm"></i>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div class="text-sm text-white">
                                        {{ \App\Models\PagoVenta::METODOS[$pago->metodo] ?? $pago->metodo }}
                                        @if ($pago->nota)
                                            <span class="text-white/40">· {{ $pago->nota }}</span>
                                        @endif
                                    </div>
                                    <div class="text-xs text-white/40">
                                        {{ $pago->fecha->format('d/m/Y H:i') }}
                                        @if ($pago->referencia)
                                            · Ref. {{ $pago->referencia }}
                                        @endif
                                        @if ($pago->user)
                                            · {{ $pago->user->name }}
                                        @endif
                                    </div>
                                </div>
                                <span class="text-sm font-semibold text-green-300">
                                    ${{ number_format($pago->monto, 0, ',', '.') }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>

        </div>

        <div class="space-y-6">
            <div class="glass-card rounded-2xl p-6 animate-delay-100">
                <h2 class="text-lg font-semibold text-white mb-4">Forma de Pago</h2>
                <div class="p-4 rounded-xl bg-white/5">
                    <p class="text-white font-medium mb-2">{{ $venta->formaPago() }}</p>
                    @if ($venta->formaPago() === 'Crédito')
                        <p class="text-xs text-white/50">Saldo pendiente: ${{ number_format($saldo, 0, ',', '.') }}</p>
                        @if ($venta->fecha_vencimiento)
                            <p class="text-xs text-white/50">Vencimiento: {{ $venta->fecha_vencimiento->format('d/m/Y') }}</p>
                        @endif
                    @endif
                </div>
            </div>

            <div class="glass-card rounded-2xl p-6 animate-delay-200">
                <h2 class="text-lg font-semibold text-white mb-4">Cliente</h2>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-white font-medium">{{ $venta->cliente?->nombre ?? '—' }}</div>
                        @if ($venta->cliente?->local)
                            <div class="text-white/50">{{ $venta->cliente->local }}</div>
                        @endif
                        @if ($venta->cliente?->ciudad)
                            <div class="text-white/40">{{ $venta->cliente->ciudad }}</div>
                        @endif
                    </div>
                    @if ($venta->cliente?->telefono)
                        <a href="tel:{{ $venta->cliente->telefono }}" class="inline-flex items-center gap-2 text-blue-300 hover:text-blue-200">
                            <i class="fas fa-phone text-xs"></i> {{ $venta->cliente->telefono }}
                        </a>
                    @endif
                    <a href="{{ route('clientes.edit', $venta->cliente) }}" class="inline-flex items-center gap-2 text-white/50 hover:text-white text-xs">
                        <i class="fas fa-pen"></i> Editar cliente
                    </a>
                </div>
            </div>

            @if ($venta->observaciones && $venta->detalles->isNotEmpty())
                <div class="glass-card rounded-2xl p-6 animate-delay-200">
                    <h2 class="text-lg font-semibold text-white mb-2">Observaciones</h2>
                    <p class="text-sm text-white/60 whitespace-pre-line">{{ $venta->observaciones }}</p>
                </div>
            @endif

            @if ($venta->estado === \App\Models\Venta::ESTADO_ANULADA)
                <div class="glass-card rounded-2xl p-6 border border-red-500/20 animate-delay-200">
                    <h2 class="text-lg font-semibold text-red-300 mb-2">Venta anulada</h2>
                    <p class="text-sm text-white/60 whitespace-pre-line">{{ $venta->motivo_anulacion }}</p>
                    <p class="text-xs text-white/40 mt-2">
                        {{ $venta->anulada_at?->format('d/m/Y H:i') }}
                        @if ($venta->anuladaPor)
                            · {{ $venta->anuladaPor->name }}
                        @endif
                    </p>
                </div>
            @elseif ($venta->pagos->isNotEmpty())
                <div class="glass-card rounded-2xl p-6 border border-yellow-500/20 animate-delay-200">
                    <p class="text-sm text-yellow-200">
                        <i class="fas fa-circle-info mr-1"></i>
                        Esta venta tiene abonos registrados, por lo que no puede anularse hasta gestionar la devolución del dinero.
                    </p>
                </div>
            @endif

            @if ($venta->detalles->isNotEmpty())
                <div class="glass-card rounded-2xl p-6 animate-delay-300">
                    <h2 class="text-lg font-semibold text-white mb-4">Movimientos de inventario</h2>
                    <a href="{{ route('inventario.movimientos.index', ['q' => $venta->numero]) }}"
                       class="text-sm text-blue-300 hover:text-blue-200">
                        <i class="fas fa-arrows-rotate mr-1"></i> Ver kardex de esta venta
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
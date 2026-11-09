@extends('layouts.app')

@section('title', 'Anular Venta '.$venta->numero)

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('ventas.show', $venta) }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">Anular Venta</h1>
                <p class="text-white/50 text-sm font-mono">{{ $venta->numero }} · {{ $venta->cliente?->nombre }}</p>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-5 mb-6">
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <div class="text-xs text-white/50 mb-1">Total</div>
                    <div class="text-lg font-bold text-white">${{ number_format($venta->total, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-xs text-white/50 mb-1">Abonado</div>
                    <div class="text-lg font-bold text-green-300">${{ number_format($venta->totalPagado(), 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-xs text-white/50 mb-1">Saldo</div>
                    <div class="text-lg font-bold text-yellow-300">${{ number_format($venta->saldo(), 0, ',', '.') }}</div>
                </div>
            </div>
        </div>

        @if ($tienePagos)
            <div class="glass rounded-2xl p-8 border border-red-500/30 bg-red-500/5">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-red-500/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-lock text-red-400 text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-red-200 mb-1">No se puede anular</h2>
                        <p class="text-sm text-white/60">
                            Esta venta tiene abonos registrados. Gestiona primero la devolución del dinero;
                            mientras existan abonos, la anulación está bloqueada para no perder el rastro de los pagos.
                        </p>
                        <a href="{{ route('ventas.show', $venta) }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                            Ver los abonos de esta venta →
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="glass rounded-2xl p-8">
                <div class="flex items-start gap-3 mb-6 p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
                    <i class="fas fa-triangle-exclamation text-yellow-400 mt-0.5"></i>
                    <p class="text-sm text-yellow-100">
                        Al anular la venta se devuelve todo el stock al inventario mediante movimientos de reversión.
                        La venta no se elimina: queda registrada como anulada.
                    </p>
                </div>

                <form method="POST" action="{{ route('ventas.anular.store', $venta) }}">
                    @csrf

                    <div>
                        <label for="motivo" class="block text-sm font-medium text-white/80 mb-2">Motivo de la anulación *</label>
                        <textarea id="motivo" name="motivo" rows="3" required class="input-glass"
                                  placeholder="Ej: error en la cantidad, devolución total del cliente...">{{ old('motivo') }}</textarea>
                        @error('motivo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-8 flex items-center gap-3">
                        <button type="submit"
                                class="flex items-center gap-2 bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg shadow-red-500/30"
                                onclick="return confirm('¿Seguro que deseas anular esta venta?')">
                            <i class="fas fa-ban"></i>
                            Anular Venta
                        </button>
                        <a href="{{ route('ventas.show', $venta) }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection

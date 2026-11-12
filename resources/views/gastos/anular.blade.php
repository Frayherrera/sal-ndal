@extends('layouts.app')

@section('title', 'Anular Gasto '.$gasto->numero)

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('gastos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">{{ $gasto->estaAnulado() ? 'Gasto anulado' : 'Anular Gasto' }}</h1>
                <p class="text-white/50 text-sm font-mono">{{ $gasto->numero }} · {{ $gasto->categoria?->nombre }}</p>
            </div>
        </div>

        <div class="glass-card rounded-2xl p-5 mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <div class="text-white font-medium">{{ $gasto->descripcion }}</div>
                    <div class="text-xs text-white/50 mt-1">
                        {{ $gasto->fecha->format('d/m/Y') }}
                        @if ($gasto->proveedor)
                            · {{ $gasto->proveedor }}
                        @endif
                        · {{ $gasto->metodoPago() }}
                    </div>
                </div>
                <div class="text-2xl font-bold text-red-300 whitespace-nowrap">${{ number_format($gasto->monto, 0, ',', '.') }}</div>
            </div>
        </div>

        @if ($gasto->estaAnulado())
            <div class="glass rounded-2xl p-8 border border-red-500/20">
                <h2 class="text-lg font-semibold text-red-300 mb-2">Motivo de la anulación</h2>
                <p class="text-sm text-white/60 whitespace-pre-line">{{ $gasto->motivo_anulacion }}</p>
                <p class="text-xs text-white/40 mt-2">
                    {{ $gasto->anulado_at?->format('d/m/Y H:i') }}
                    @if ($gasto->anuladoPor)
                        · {{ $gasto->anuladoPor->name }}
                    @endif
                </p>
            </div>
        @elseif ($gasto->esAutomatico())
            <div class="glass rounded-2xl p-8 border border-purple-500/30 bg-purple-500/5">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                        <i class="fas fa-bolt text-purple-300 text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-purple-200 mb-1">Gasto generado por una compra</h2>
                        <p class="text-sm text-white/60">
                            Este gasto se creó al registrar una compra en inventario. Para corregirlo, anula la compra:
                            el gasto se anula automáticamente junto con ella.
                        </p>
                        <a href="{{ route('inventario.movimientos.show', $gasto->movimiento_inventario_id) }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">
                            Ver la compra en Movimientos →
                        </a>
                    </div>
                </div>
            </div>
        @else
            <div class="glass rounded-2xl p-8">
                <div class="flex items-start gap-3 mb-6 p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
                    <i class="fas fa-triangle-exclamation text-yellow-400 mt-0.5"></i>
                    <p class="text-sm text-yellow-100">
                        El gasto dejará de sumar en los totales y reportes. No se elimina: queda registrado como anulado.
                    </p>
                </div>

                <form method="POST" action="{{ route('gastos.anular.store', $gasto) }}">
                    @csrf

                    <div>
                        <label for="motivo" class="block text-sm font-medium text-white/80 mb-2">Motivo de la anulación *</label>
                        <textarea id="motivo" name="motivo" rows="3" required class="input-glass"
                                  placeholder="Ej: registrado dos veces, valor equivocado...">{{ old('motivo') }}</textarea>
                        @error('motivo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div class="mt-8 flex items-center gap-3">
                        <button type="submit"
                                class="flex items-center gap-2 bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg shadow-red-500/30"
                                onclick="return confirm('¿Seguro que deseas anular este gasto?')">
                            <i class="fas fa-ban"></i>
                            Anular Gasto
                        </button>
                        <a href="{{ route('gastos.index') }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                    </div>
                </form>
            </div>
        @endif
    </div>
@endsection

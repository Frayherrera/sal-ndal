@extends('layouts.app')

@section('title', 'Anular Movimiento #' . $movimiento->id)

@section('content')
    <div class="max-w-xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.movimientos.show', $movimiento) }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Anular Movimiento #{{ $movimiento->id }}</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 mb-6 flex items-start gap-3">
                <i class="fas fa-triangle-exclamation text-yellow-400 mt-0.5"></i>
                <div class="text-sm text-yellow-200">
                    <p class="font-medium mb-1">Se revertirá el stock afectado.</p>
                    <p>
                        {{ $movimiento->origen?->nombre }} ·
                        {{ \App\Models\MovimientoInventario::TIPOS[$movimiento->tipo] ?? $movimiento->tipo }} ·
                        {{ ($movimiento->direccion === 'entrada' ? '+' : '-') . number_format($movimiento->cantidad, 0) }}
                    </p>
                    <p class="mt-1 text-xs opacity-80">No se borra el movimiento, se crea una reversión de auditoría.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('inventario.movimientos.anular.store', $movimiento) }}">
                @csrf

                <div class="mb-5">
                    <label class="block text-sm font-medium text-white/80 mb-2">Motivo de la anulación *</label>
                    <textarea name="motivo" rows="3" required class="input-glass"
                              placeholder="¿Por qué se anula este movimiento?">{{ old('motivo') }}</textarea>
                    @error('motivo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center gap-3">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-red-500 to-rose-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-red-600 hover:to-rose-700 transition-all shadow-lg shadow-red-500/30">
                        <i class="fas fa-rotate-left"></i>
                        Confirmar Anulación
                    </button>
                    <a href="{{ route('inventario.movimientos.show', $movimiento) }}"
                       class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                </div>
            </form>
        </div>
    </div>
@endsection

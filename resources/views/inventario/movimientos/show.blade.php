@extends('layouts.app')

@section('title', 'Movimiento #' . $movimiento->id)

@section('content')
    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('inventario.movimientos.index') }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <h1 class="text-2xl font-bold text-white">Movimiento #{{ $movimiento->id }}</h1>
        </div>

        <div class="glass rounded-2xl p-8">
            <div class="flex items-center gap-4 mb-6">
                <div class="w-14 h-14 rounded-2xl flex items-center justify-center
                    {{ $movimiento->direccion === 'entrada' ? 'bg-green-500/20 text-green-400' : 'bg-red-500/20 text-red-400' }}">
                    <i class="fas {{ $movimiento->direccion === 'entrada' ? 'fa-arrow-down' : 'fa-arrow-up' }} text-xl"></i>
                </div>
                <div>
                    <span class="px-3 py-1 rounded-full text-xs font-medium
                        {{ $movimiento->direccion === 'entrada' ? 'bg-green-500/20 text-green-300' : 'bg-red-500/20 text-red-300' }}">
                        {{ \App\Models\MovimientoInventario::TIPOS[$movimiento->tipo] ?? $movimiento->tipo }}
                    </span>
                    <p class="text-white/50 text-sm mt-1">{{ $movimiento->fecha->format('d/m/Y H:i') }}</p>
                </div>
            </div>

            <dl class="space-y-3 text-sm">
                <div class="flex justify-between border-b border-white/5 pb-3">
                    <dt class="text-white/50">Artículo</dt>
                    <dd class="text-white font-medium">{{ $movimiento->origen?->nombre ?? 'N/D' }}
                        <span class="text-white/40 text-xs">({{ is_object($movimiento->origen) ? class_basename($movimiento->origen) : '' }})</span>
                    </dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-3">
                    <dt class="text-white/50">Cantidad</dt>
                    <dd class="font-semibold {{ $movimiento->direccion === 'entrada' ? 'text-green-300' : 'text-red-300' }}">
                        {{ ($movimiento->direccion === 'entrada' ? '+' : '-') . number_format($movimiento->cantidad, 0) }}
                        @if ($movimiento->origen instanceof \App\Models\MateriaPrima) g @else u @endif
                    </dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-3">
                    <dt class="text-white/50">Saldo resultante</dt>
                    <dd class="text-white font-medium">{{ number_format($movimiento->saldo, 0) }}</dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-3">
                    <dt class="text-white/50">Documento</dt>
                    <dd class="text-white">{{ $movimiento->documento ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-3">
                    <dt class="text-white/50">Referencia</dt>
                    <dd class="text-white">{{ $movimiento->referencia ?? '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-3">
                    <dt class="text-white/50">Costo total</dt>
                    <dd class="text-white">{{ $movimiento->costo_total ? '$' . number_format($movimiento->costo_total, 2) : '—' }}</dd>
                </div>
                <div class="flex justify-between border-b border-white/5 pb-3">
                    <dt class="text-white/50">Registrado por</dt>
                    <dd class="text-white">{{ $movimiento->user?->name ?? '—' }}</dd>
                </div>
                @if ($movimiento->motivo)
                    <div class="flex justify-between">
                        <dt class="text-white/50">Motivo</dt>
                        <dd class="text-white/80 text-right max-w-[60%]">{{ $movimiento->motivo }}</dd>
                    </div>
                @endif
            </dl>

            @if ($movimiento->tipo !== 'anulacion_reversion' && !$movimiento->movimiento_original_id)
                <div class="mt-8 border-t border-white/10 pt-6">
                    <a href="{{ route('inventario.movimientos.anular', $movimiento) }}"
                       class="flex items-center justify-center gap-2 bg-red-500/20 text-red-300 px-4 py-3 rounded-xl font-medium hover:bg-red-500/30 transition-colors">
                        <i class="fas fa-rotate-left"></i>
                        Anular / Revertir este movimiento
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection

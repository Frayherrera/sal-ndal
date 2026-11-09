@extends('layouts.app')

@section('title', 'Registrar Abono')

@section('content')
    @php
        $saldo = $venta->saldo();
    @endphp

    <div class="max-w-2xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('ventas.show', $venta) }}" class="text-white/60 hover:text-white transition-colors">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-white">Registrar Abono</h1>
                <p class="text-white/50 text-sm font-mono">{{ $venta->numero }} · {{ $venta->cliente?->nombre }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 mb-6">
            <div class="glass-card rounded-2xl p-4">
                <div class="text-xs text-white/50 mb-1">Total</div>
                <div class="text-lg font-bold text-white">${{ number_format($venta->total, 0, ',', '.') }}</div>
            </div>
            <div class="glass-card rounded-2xl p-4">
                <div class="text-xs text-white/50 mb-1">Abonado</div>
                <div class="text-lg font-bold text-green-300">${{ number_format($venta->totalPagado(), 0, ',', '.') }}</div>
            </div>
            <div class="glass-card rounded-2xl p-4">
                <div class="text-xs text-white/50 mb-1">Saldo</div>
                <div class="text-lg font-bold text-yellow-300">${{ number_format($saldo, 0, ',', '.') }}</div>
            </div>
        </div>

        <div class="glass rounded-2xl p-8">
            @if ($saldo <= 0)
                <div class="text-center py-8">
                    <i class="fas fa-circle-check text-4xl text-green-400 mb-4"></i>
                    <p class="text-white/70">Esta venta ya está pagada en su totalidad.</p>
                    <a href="{{ route('ventas.show', $venta) }}" class="inline-block mt-4 text-blue-300 hover:text-blue-200 text-sm">Volver a la venta →</a>
                </div>
            @else
                <form method="POST" action="{{ route('ventas.abonos.store', $venta) }}">
                    @csrf

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label for="monto" class="block text-sm font-medium text-white/80 mb-2">Monto *</label>
                            <input type="number" id="monto" name="monto" min="0.01" step="0.01" max="{{ $saldo }}"
                                   value="{{ old('monto', $saldo) }}" required class="input-glass">
                            @error('monto') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="metodo" class="block text-sm font-medium text-white/80 mb-2">Método *</label>
                            <select id="metodo" name="metodo" required class="input-glass bg-gray-900/60">
                                @foreach ($metodos as $key => $label)
                                    <option value="{{ $key }}" @selected(old('metodo', 'efectivo') === $key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('metodo') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="fecha" class="block text-sm font-medium text-white/80 mb-2">Fecha</label>
                            <input type="date" id="fecha" name="fecha"
                                   value="{{ old('fecha', now()->toDateString()) }}" class="input-glass">
                            @error('fecha') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div>
                            <label for="referencia" class="block text-sm font-medium text-white/80 mb-2">Referencia</label>
                            <input type="text" id="referencia" name="referencia" maxlength="100"
                                   value="{{ old('referencia') }}" class="input-glass" placeholder="Nro. transferencia, etc.">
                            @error('referencia') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>

                        <div class="sm:col-span-2">
                            <label for="nota" class="block text-sm font-medium text-white/80 mb-2">Nota</label>
                            <textarea id="nota" name="nota" rows="2" class="input-glass"
                                      placeholder="Opcional">{{ old('nota') }}</textarea>
                            @error('nota') <p class="text-red-400 text-xs mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>

                    <div class="mt-8 flex items-center gap-3">
                        <button type="submit"
                                class="flex items-center gap-2 bg-gradient-to-r from-green-500 to-teal-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-green-600 hover:to-teal-700 transition-all shadow-lg shadow-green-500/30">
                            <i class="fas fa-hand-holding-dollar"></i>
                            Registrar Abono
                        </button>
                        <a href="{{ route('ventas.show', $venta) }}" class="text-white/60 hover:text-white px-4 py-3 transition-colors text-sm">Cancelar</a>
                    </div>
                </form>
            @endif
        </div>
    </div>
@endsection

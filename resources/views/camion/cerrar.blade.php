@extends('layouts.app')

@section('title', 'Regreso del viaje '.$viaje->numero)

@php use App\Support\Paquetes; @endphp

@section('content')
    <div class="max-w-6xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('camion.show', $viaje) }}" class="text-white/60 hover:text-white transition-colors"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-white">Regreso del camión</h1>
                <p class="text-white/50 text-sm">
                    <span class="font-mono">{{ $viaje->numero }}</span> · {{ $viaje->ruta?->nombre }} · salió el {{ $viaje->fecha_salida->format('d/m/Y') }}
                </p>
            </div>
        </div>

        @if ($errors->any())
            <div class="glass-card rounded-xl p-4 mb-6 border border-red-500/30 bg-red-500/10">
                <ul class="text-sm text-red-200 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('camion.cerrar.store', $viaje) }}">
            @csrf

            <div class="glass rounded-2xl p-6 mb-6 grid sm:grid-cols-3 gap-5 items-end">
                <div>
                    <label for="fecha_regreso" class="block text-sm font-medium text-white/80 mb-2">Fecha de regreso *</label>
                    <input type="date" id="fecha_regreso" name="fecha_regreso" required
                           min="{{ $viaje->fecha_salida->toDateString() }}" max="{{ now()->toDateString() }}"
                           value="{{ old('fecha_regreso', now()->toDateString()) }}" class="input-glass">
                </div>
                <p class="sm:col-span-2 text-sm text-white/60">
                    Cuenta lo que volvió en el camión. Lo dañado y lo regalado se anotan aparte para que no cuenten como venta.
                    <b class="text-white">Vendido = salió − regresó − dañado − regalado.</b>
                </p>
            </div>

            <div class="glass rounded-2xl p-6 mb-6 overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-white/50 border-b border-white/10">
                            <th class="p-3 min-w-[180px]">Producto</th>
                            <th class="p-3 text-right">Salió</th>
                            <th class="p-3">Regresó bien</th>
                            <th class="p-3">Dañado</th>
                            <th class="p-3">Regalado</th>
                            <th class="p-3 text-right">Vendido</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detalles as $detalle)
                            @php
                                $pid = $detalle->producto_terminado_id;
                                $old = old("lineas.{$pid}", []);
                            @endphp
                            <tr class="border-b border-white/5 fila-cierre" data-cargado="{{ $detalle->cargado }}">
                                <td class="p-3">
                                    <div class="text-white font-medium">{{ $detalle->producto?->nombre }}</div>
                                    <div class="text-xs text-white/40">{{ $detalle->producto?->presentacion }}</div>
                                </td>
                                <td class="p-3 text-right text-white/80 whitespace-nowrap">{{ Paquetes::texto($detalle->cargado) }}</td>
                                @foreach (['regresado' => 'Regresó', 'danado' => 'Dañado', 'regalado' => 'Regalado'] as $campo => $etiqueta)
                                    <td class="p-3">
                                        <div class="flex gap-1">
                                            <input type="number" min="0" step="1" name="lineas[{{ $pid }}][{{ $campo }}_p]" value="{{ $old[$campo.'_p'] ?? '' }}"
                                                   class="input-glass w-[4.5rem] text-right cant {{ $campo }}-p" placeholder="0" aria-label="{{ $etiqueta }} paquetes de {{ $detalle->producto?->nombre }}">
                                            <input type="number" min="0" step="1" name="lineas[{{ $pid }}][{{ $campo }}_u]" value="{{ $old[$campo.'_u'] ?? '' }}"
                                                   class="input-glass w-14 text-right cant {{ $campo }}-u" placeholder="u" aria-label="{{ $etiqueta }} unidades sueltas de {{ $detalle->producto?->nombre }}">
                                        </div>
                                    </td>
                                @endforeach
                                <td class="p-3 text-right font-semibold text-white whitespace-nowrap vendido">{{ Paquetes::texto($detalle->cargado) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="glass rounded-2xl p-5 mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1 text-white">
                    Vendido en el viaje: <b id="total-vendido">0</b>
                    <span class="text-white/50">· merma <span id="total-merma">0</span></span>
                    <p id="aviso-negativo" class="hidden text-sm text-red-300 mt-1">Hay productos donde lo anotado supera lo que salió.</p>
                </div>
                <button type="submit"
                        class="flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30"
                        onclick="return confirm('¿Cerrar el viaje? Lo que regresó bien queda en el camión para el siguiente viaje.')">
                    <i class="fas fa-flag-checkered"></i> Cerrar viaje
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const POR_PAQUETE = {{ Paquetes::UNIDADES }};
    const filas = [...document.querySelectorAll('.fila-cierre')];
    const num = (el) => el ? Math.max(parseInt(el.value || '0', 10) || 0, 0) : 0;
    const unidades = (fila, campo) => num(fila.querySelector('.' + campo + '-p')) * POR_PAQUETE + num(fila.querySelector('.' + campo + '-u'));

    function texto(u) {
        if (u === 0) return '0';
        const signo = u < 0 ? '−' : '';
        u = Math.abs(u);
        const p = Math.floor(u / POR_PAQUETE), s = u % POR_PAQUETE;
        return signo + [p > 0 ? p.toLocaleString('es-CO') + ' p' : null, s > 0 ? s + ' u' : null].filter(Boolean).join(' + ');
    }

    function calcular() {
        let vendidoTotal = 0, mermaTotal = 0, negativo = false;
        filas.forEach((fila) => {
            const merma = unidades(fila, 'danado') + unidades(fila, 'regalado');
            const vendido = parseInt(fila.dataset.cargado, 10) - unidades(fila, 'regresado') - merma;
            const celda = fila.querySelector('.vendido');
            celda.textContent = texto(vendido);
            celda.classList.toggle('text-red-300', vendido < 0);
            negativo = negativo || vendido < 0;
            vendidoTotal += Math.max(vendido, 0);
            mermaTotal += merma;
        });
        document.getElementById('total-vendido').textContent = texto(vendidoTotal);
        document.getElementById('total-merma').textContent = texto(mermaTotal);
        document.getElementById('aviso-negativo').classList.toggle('hidden', !negativo);
    }

    document.querySelectorAll('.cant').forEach((el) => el.addEventListener('input', calcular));
    calcular();
})();
</script>
@endpush

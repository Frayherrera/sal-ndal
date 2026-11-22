@extends('layouts.app')

@section('title', 'Cargar camión')

@php use App\Support\Paquetes; @endphp

@section('content')
    <div class="max-w-6xl mx-auto animate-fade-in">
        <div class="flex items-center gap-3 mb-6">
            <a href="{{ route('camion.index') }}" class="text-white/60 hover:text-white transition-colors"><i class="fas fa-arrow-left"></i></a>
            <div>
                <h1 class="text-2xl font-bold text-white">Surtir y cargar el camión</h1>
                <p class="text-white/50 text-sm">Parte de lo que hay en el camión; anota lo que surtes desde bodega y lo que bajas.</p>
            </div>
        </div>

        @if ($errors->any())
            <div class="glass-card rounded-xl p-4 mb-6 border border-red-500/30 bg-red-500/10">
                <ul class="text-sm text-red-200 space-y-1">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <form method="POST" action="{{ route('camion.store') }}" id="cargue-form">
            @csrf

            <div class="glass rounded-2xl p-6 mb-6">
                <div class="grid sm:grid-cols-3 gap-5">
                    <div>
                        <label for="ruta_id" class="block text-sm font-medium text-white/80 mb-2">Ruta de esta semana *</label>
                        <select id="ruta_id" name="ruta_id" class="input-glass bg-gray-900/60"
                                onchange="window.location='{{ route('camion.create') }}?ruta_id='+this.value">
                            @foreach ($rutas as $r)
                                <option value="{{ $r->id }}" @selected($r->id === $ruta->id)>{{ $r->orden }}. {{ $r->nombre }}</option>
                            @endforeach
                        </select>
                        <p class="text-xs text-white/40 mt-1">Se sugiere la siguiente en la rotación.</p>
                    </div>
                    <div>
                        <label for="fecha_salida" class="block text-sm font-medium text-white/80 mb-2">Fecha de salida *</label>
                        <input type="date" id="fecha_salida" name="fecha_salida" required max="{{ now()->toDateString() }}"
                               value="{{ old('fecha_salida', now()->toDateString()) }}" class="input-glass">
                    </div>
                    <div>
                        <label for="observaciones" class="block text-sm font-medium text-white/80 mb-2">Observaciones</label>
                        <input type="text" id="observaciones" name="observaciones" maxlength="1000"
                               value="{{ old('observaciones') }}" class="input-glass" placeholder="Opcional">
                    </div>
                </div>
            </div>

            <div class="glass rounded-2xl p-6 mb-6">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 mb-4">
                    <input type="search" id="filtro" placeholder="Buscar producto…" class="input-glass sm:max-w-xs">
                    <label class="flex items-center gap-2 text-sm text-white/70 cursor-pointer select-none">
                        <input type="checkbox" id="solo-movimiento" class="w-4 h-4 accent-blue-500"> Solo con carga o surtido
                    </label>
                    <button type="button" id="usar-sugerencia"
                            class="sm:ml-auto flex items-center gap-2 bg-purple-500/20 text-purple-200 px-4 py-2 rounded-xl text-sm font-medium hover:bg-purple-500/30 transition-colors">
                        <i class="fas fa-wand-magic-sparkles"></i> Llenar con la sugerencia
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm" id="tabla-cargue">
                        <thead>
                            <tr class="text-left text-white/50 border-b border-white/10">
                                <th class="p-3 min-w-[200px]">Producto</th>
                                <th class="p-3 text-right">En camión</th>
                                <th class="p-3 text-right hidden md:table-cell">Bodega</th>
                                <th class="p-3 text-right hidden md:table-cell" title="Promedio vendido en los últimos viajes de esta ruta">Vende aprox.</th>
                                <th class="p-3">Surtir (paq. + u)</th>
                                <th class="p-3">Bajar (paq. + u)</th>
                                <th class="p-3 text-right">Sale con</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($productos as $fila)
                                @php
                                    $p = $fila['producto'];
                                    $old = old("lineas.{$p->id}", []);
                                    $sugerido = max(0, $fila['promedio'] - $fila['en_camion']);
                                    $sugeridoPaquetes = (int) ceil($sugerido / Paquetes::UNIDADES);
                                @endphp
                                <tr class="border-b border-white/5 fila-producto"
                                    data-nombre="{{ \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii($p->nombre)) }}"
                                    data-en-camion="{{ $fila['en_camion'] }}"
                                    data-sugerido="{{ $sugeridoPaquetes }}">
                                    <td class="p-3">
                                        <div class="text-white font-medium">{{ $p->nombre }}</div>
                                        <div class="text-xs text-white/40">{{ $p->presentacion }}</div>
                                    </td>
                                    <td class="p-3 text-right text-white/80 whitespace-nowrap">{{ Paquetes::texto($fila['en_camion']) }}</td>
                                    <td class="p-3 text-right whitespace-nowrap hidden md:table-cell {{ $fila['bodega'] <= 0 ? 'text-red-300' : 'text-white/60' }}">{{ Paquetes::texto($fila['bodega']) }}</td>
                                    <td class="p-3 text-right text-purple-200 whitespace-nowrap hidden md:table-cell">{{ $fila['promedio'] > 0 ? Paquetes::texto($fila['promedio']) : '—' }}</td>
                                    <td class="p-3">
                                        <div class="flex gap-1">
                                            <input type="number" min="0" step="1" name="lineas[{{ $p->id }}][surtido_p]" value="{{ $old['surtido_p'] ?? '' }}"
                                                   class="input-glass w-20 text-right cant surtido-p" placeholder="0" aria-label="Paquetes a surtir de {{ $p->nombre }}">
                                            <input type="number" min="0" step="1" name="lineas[{{ $p->id }}][surtido_u]" value="{{ $old['surtido_u'] ?? '' }}"
                                                   class="input-glass w-16 text-right cant surtido-u" placeholder="u" aria-label="Unidades sueltas a surtir de {{ $p->nombre }}">
                                        </div>
                                    </td>
                                    <td class="p-3">
                                        @if ($fila['en_camion'] > 0)
                                            <div class="flex gap-1">
                                                <input type="number" min="0" step="1" name="lineas[{{ $p->id }}][bajado_p]" value="{{ $old['bajado_p'] ?? '' }}"
                                                       class="input-glass w-20 text-right cant bajado-p" placeholder="0" aria-label="Paquetes a bajar de {{ $p->nombre }}">
                                                <input type="number" min="0" step="1" name="lineas[{{ $p->id }}][bajado_u]" value="{{ $old['bajado_u'] ?? '' }}"
                                                       class="input-glass w-16 text-right cant bajado-u" placeholder="u" aria-label="Unidades sueltas a bajar de {{ $p->nombre }}">
                                            </div>
                                        @else
                                            <span class="text-white/30">—</span>
                                        @endif
                                    </td>
                                    <td class="p-3 text-right font-semibold text-white whitespace-nowrap sale-con">{{ Paquetes::texto($fila['en_camion']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="glass rounded-2xl p-5 mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
                <div class="flex-1 text-white">
                    El camión sale con <b id="total-sale">0</b>
                    <span class="text-white/50">· se surten <span id="total-surtido">0</span> de bodega</span>
                </div>
                <button type="submit"
                        class="flex items-center justify-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30"
                        onclick="return confirm('¿Cargar el camión? El surtido se descuenta del stock de bodega.')">
                    <i class="fas fa-truck-fast"></i> Cargar y salir a ruta
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
<script>
(function () {
    const POR_PAQUETE = {{ Paquetes::UNIDADES }};
    const filas = [...document.querySelectorAll('.fila-producto')];
    const num = (el) => el ? Math.max(parseInt(el.value || '0', 10) || 0, 0) : 0;

    function texto(u) {
        if (u === 0) return '0';
        const signo = u < 0 ? '−' : '';
        u = Math.abs(u);
        const p = Math.floor(u / POR_PAQUETE), s = u % POR_PAQUETE;
        return signo + [p > 0 ? p.toLocaleString('es-CO') + ' p' : null, s > 0 ? s + ' u' : null].filter(Boolean).join(' + ');
    }

    function calcular() {
        let totalSale = 0, totalSurtido = 0;
        filas.forEach((fila) => {
            const enCamion = parseInt(fila.dataset.enCamion, 10);
            const surtido = num(fila.querySelector('.surtido-p')) * POR_PAQUETE + num(fila.querySelector('.surtido-u'));
            const bajado = num(fila.querySelector('.bajado-p')) * POR_PAQUETE + num(fila.querySelector('.bajado-u'));
            const sale = enCamion + surtido - bajado;
            const celda = fila.querySelector('.sale-con');
            celda.textContent = texto(sale);
            celda.classList.toggle('text-red-300', sale < 0);
            fila.dataset.movimiento = (enCamion > 0 || surtido > 0) ? '1' : '0';
            totalSale += Math.max(sale, 0);
            totalSurtido += surtido;
        });
        document.getElementById('total-sale').textContent = texto(totalSale);
        document.getElementById('total-surtido').textContent = texto(totalSurtido);
        filtrar();
    }

    function filtrar() {
        const q = document.getElementById('filtro').value.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().trim();
        const solo = document.getElementById('solo-movimiento').checked;
        filas.forEach((fila) => {
            const visible = (!q || fila.dataset.nombre.includes(q)) && (!solo || fila.dataset.movimiento === '1');
            fila.style.display = visible ? '' : 'none';
        });
    }

    document.getElementById('usar-sugerencia').addEventListener('click', () => {
        filas.forEach((fila) => {
            const input = fila.querySelector('.surtido-p');
            const sugerido = parseInt(fila.dataset.sugerido, 10);
            if (sugerido > 0 && !input.value) input.value = sugerido;
        });
        calcular();
    });

    document.querySelectorAll('.cant').forEach((el) => el.addEventListener('input', calcular));
    document.getElementById('filtro').addEventListener('input', filtrar);
    document.getElementById('solo-movimiento').addEventListener('change', filtrar);
    calcular();
})();
</script>
@endpush

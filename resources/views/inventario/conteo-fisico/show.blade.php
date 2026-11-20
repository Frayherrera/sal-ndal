@extends('layouts.app')

@section('title', 'Conteo ' . $conteo->codigo)

@section('content')
@php
    $esMateria = $conteo->tipo === 'materia_prima';
    $editable = $conteo->estado === 'borrador';
    $campo = $esMateria ? 'materia_prima_id' : 'producto_terminado_id';

    // Artículos del tipo del conteo, con su unidad de captura y stock actual en esa unidad.
    // La materia prima se digita en su unidad_base (kg o g); internamente se guarda en gramos.
    $articulos = ($esMateria
        ? \App\Models\MateriaPrima::where('activo', true)->with('inventario')->orderBy('nombre')->get()
            ->map(fn ($m) => [
                'id' => $m->id,
                'nombre' => $m->nombre,
                'unidad' => $m->unidad_base === 'kg' ? 'kg' : 'g',
                'stock' => $m->unidad_base === 'kg' ? $m->stock_gramos() / 1000 : $m->stock_gramos(),
            ])
        : \App\Models\ProductoTerminado::where('activo', true)->with('inventario')->orderBy('nombre')->get()
            ->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'unidad' => 'u',
                'stock' => $p->stock_disponible(),
            ])
    )->values();

    // Convierte una cantidad interna (gramos / unidades) a la unidad de captura del artículo.
    $unidadDe = fn ($detalle) => $detalle->materiaPrima
        ? ($detalle->materiaPrima->unidad_base === 'kg' ? 'kg' : 'g')
        : 'u';
    $enUnidad = fn ($valor, string $unidad) => $unidad === 'kg' ? $valor / 1000 : $valor;
    // En kg se muestran hasta 3 decimales sin ceros sobrantes; en g y u, enteros.
    $fmt = fn ($valor, string $unidad) => $unidad === 'kg'
        ? rtrim(rtrim(number_format($valor, 3, ',', '.'), '0'), ',')
        : number_format($valor, 0, ',', '.');

    // Filas iniciales del formulario: lo enviado si hubo error, o las líneas guardadas.
    $filasIniciales = old('detalles') !== null
        ? collect(old('detalles'))->map(fn ($d) => [
            'id' => $d[$campo] ?? '',
            'cantidad' => $d['cantidad_fisica'] ?? '',
            'motivo' => $d['motivo'] ?? '',
        ])->values()
        : $conteo->detalles->map(fn ($d) => [
            'id' => $d->{$campo},
            'cantidad' => $enUnidad($d->cantidad_fisica, $unidadDe($d)),
            'motivo' => $d->motivo ?? '',
        ])->values();
@endphp

    <div class="flex items-center gap-3 mb-6 animate-fade-in">
        <a href="{{ route('inventario.conteo-fisico.index') }}" class="text-white/60 hover:text-white transition-colors">
            <i class="fas fa-arrow-left"></i>
        </a>
        <div class="flex-1">
            <h1 class="text-2xl font-bold text-white">Conteo {{ $conteo->codigo }}</h1>
            <p class="text-white/50 text-sm">
                {{ $esMateria ? 'Materia prima' : 'Producto terminado' }} ·
                {{ $conteo->fecha_conteo->format('d/m/Y') }}
            </p>
        </div>
        <span class="px-3 py-1 rounded-full text-xs font-medium
            @switch($conteo->estado)
                @case('borrador') bg-gray-500/20 text-gray-300 @break
                @case('completado') bg-blue-500/20 text-blue-300 @break
                @case('aprobado') bg-green-500/20 text-green-300 @break
                @case('anulado') bg-red-500/20 text-red-300 @break
            @endswitch">
            {{ \App\Models\ConteoFisico::ESTADOS[$conteo->estado] ?? $conteo->estado }}
        </span>
    </div>

    @if (session('success'))
        <div class="glass-card rounded-xl p-4 border border-green-500/30 bg-green-500/10 flex items-center gap-3 mb-6 animate-fade-in">
            <i class="fas fa-check-circle text-green-400"></i>
            <span class="text-green-200 text-sm">{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="glass-card rounded-xl p-4 border border-red-500/30 bg-red-500/10 flex items-center gap-3 mb-6 animate-fade-in">
            <i class="fas fa-exclamation-circle text-red-400"></i>
            <span class="text-red-200 text-sm">{{ session('error') }}</span>
        </div>
    @endif

    @if ($conteo->observaciones)
        <div class="glass-card rounded-xl p-4 mb-6 text-white/60 text-sm animate-delay-100 whitespace-pre-wrap">
            {{ $conteo->observaciones }}
        </div>
    @endif

    <!-- Acciones del flujo -->
    @if ($conteo->estado === 'borrador')
        <div class="glass-card rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-3 animate-delay-100">
            <form method="POST" action="{{ route('inventario.conteo-fisico.completar', $conteo) }}"
                  onsubmit="return confirm('¿Completar el conteo? Las líneas quedarán fijas para revisión y ya no podrás modificarlas.')">
                @csrf
                <button type="submit" class="flex items-center gap-2 bg-blue-500/20 text-blue-300 px-4 py-2.5 rounded-xl font-medium hover:bg-blue-500/30 transition-colors">
                    <i class="fas fa-check-double"></i> Completar
                </button>
            </form>
            <form method="POST" action="{{ route('inventario.conteo-fisico.anular', $conteo) }}"
                  onsubmit="return confirm('¿Anular este conteo?')">
                @csrf
                <input type="hidden" name="motivo" value="Anulado desde el flujo de borrador">
                <button type="submit" class="flex items-center gap-2 bg-red-500/20 text-red-300 px-4 py-2.5 rounded-xl font-medium hover:bg-red-500/30 transition-colors">
                    <i class="fas fa-ban"></i> Anular
                </button>
            </form>
            <p class="text-xs text-white/40">Guarda las líneas antes de completar.</p>
        </div>
    @endif

    @if ($conteo->estado === 'completado')
        <div class="glass-card rounded-2xl p-4 mb-6 flex flex-wrap items-center gap-3 animate-delay-100">
            <form method="POST" action="{{ route('inventario.conteo-fisico.aprobar', $conteo) }}"
                  onsubmit="return confirm('¿Aprobar el conteo? Se generarán los ajustes de inventario por cada diferencia.')">
                @csrf
                <button type="submit" class="flex items-center gap-2 bg-green-500/20 text-green-300 px-4 py-2.5 rounded-xl font-medium hover:bg-green-500/30 transition-colors">
                    <i class="fas fa-check-circle"></i> Aprobar y Generar Ajustes
                </button>
            </form>
            <form method="POST" action="{{ route('inventario.conteo-fisico.anular', $conteo) }}"
                  onsubmit="return confirm('¿Anular este conteo? No se generará ningún ajuste.')">
                @csrf
                <input type="hidden" name="motivo" value="Anulado en revisión">
                <button type="submit" class="flex items-center gap-2 bg-red-500/20 text-red-300 px-4 py-2.5 rounded-xl font-medium hover:bg-red-500/30 transition-colors">
                    <i class="fas fa-ban"></i> Anular
                </button>
            </form>
            <p class="text-xs text-white/40">Si hay un error en las líneas, anula el conteo y crea uno nuevo.</p>
        </div>
    @endif

    <!-- Captura de líneas (solo borrador) -->
    @if ($editable)
        <div class="glass rounded-2xl p-6 sm:p-8 mb-6 animate-delay-100">
            <h2 class="text-lg font-semibold text-white mb-2">Registrar líneas</h2>
            <p class="text-white/50 text-sm mb-6">
                Elige el artículo y escribe la cantidad que contaste
                {{ $esMateria ? '(en la unidad de cada materia prima: kg o g)' : '(en unidades)' }}.
                El stock del sistema se toma al guardar y la diferencia se calcula sola.
            </p>

            <form method="POST" action="{{ route('inventario.conteo-fisico.detalles', $conteo) }}" id="conteoForm">
                @csrf

                <div class="space-y-3" id="detalleRows"></div>

                <button type="button" id="addRowBtn"
                        class="mt-4 flex items-center gap-2 text-blue-300 hover:text-blue-200 text-sm font-medium">
                    <i class="fas fa-plus"></i> Agregar línea
                </button>

                <div class="mt-6">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i> Guardar líneas
                    </button>
                </div>
            </form>
        </div>

        <template id="filaTemplate">
            <div class="glass-card rounded-xl p-4 detail-row">
                <div class="flex flex-wrap gap-3 items-end">
                    <div class="flex-1 min-w-[200px]">
                        <label class="block text-xs text-white/50 mb-1">{{ $esMateria ? 'Materia prima' : 'Producto' }}</label>
                        <select data-name="{{ $campo }}" class="input-glass bg-gray-900/60 item-select">
                            <option value="">Seleccionar...</option>
                            @foreach ($articulos as $a)
                                <option value="{{ $a['id'] }}">{{ $a['nombre'] }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs text-white/50 mb-1">Stock sistema</label>
                        <div class="input-glass bg-gray-900/50 text-white/60 stock-sistema">—</div>
                    </div>
                    <div class="w-32">
                        <label class="block text-xs text-white/50 mb-1">Contado <span class="unidad"></span></label>
                        <input type="number" min="0" step="any" data-name="cantidad_fisica" class="input-glass cantidad-fisica">
                    </div>
                    <div class="w-28">
                        <label class="block text-xs text-white/50 mb-1">Diferencia</label>
                        <div class="input-glass bg-gray-900/50 font-semibold diferencia text-white/50">—</div>
                    </div>
                    <div class="flex-1 min-w-[180px]">
                        <label class="block text-xs text-white/50 mb-1">Motivo (si hay diferencia)</label>
                        <input type="text" data-name="motivo" maxlength="255" class="input-glass motivo" placeholder="Ej: merma, sobrante">
                    </div>
                    <button type="button" title="Quitar línea"
                            class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 remove-row">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        </template>
    @endif

    <!-- Líneas guardadas -->
    <div class="glass-card rounded-2xl p-6 animate-delay-200 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-white">Líneas del conteo</h2>
            <span class="text-xs text-white/40">{{ $conteo->detalles->count() }} líneas</span>
        </div>

        @if ($conteo->detalles->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-clipboard-list text-3xl text-white/20 mb-3"></i>
                <p class="text-white/50 text-sm">Aún no hay líneas guardadas.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="py-2 pr-4">Artículo</th>
                        <th class="py-2 pr-4 text-right">Stock sistema</th>
                        <th class="py-2 pr-4 text-right">Contado</th>
                        <th class="py-2 pr-4 text-right">Diferencia</th>
                        <th class="py-2">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($conteo->detalles as $detalle)
                        @php
                            $nombre = $detalle->materiaPrima?->nombre ?? $detalle->productoTerminado?->nombre ?? 'N/D';
                            $u = $unidadDe($detalle);
                            $dif = $enUnidad($detalle->diferencia, $u);
                        @endphp
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-2.5 pr-4 text-white font-medium">{{ $nombre }}</td>
                            <td class="py-2.5 pr-4 text-right text-white/60 whitespace-nowrap">{{ $fmt($enUnidad($detalle->stock_sistema, $u), $u) }} {{ $u }}</td>
                            <td class="py-2.5 pr-4 text-right text-white whitespace-nowrap">{{ $fmt($enUnidad($detalle->cantidad_fisica, $u), $u) }} {{ $u }}</td>
                            <td class="py-2.5 pr-4 text-right font-semibold whitespace-nowrap {{ $detalle->diferencia > 0 ? 'text-green-300' : ($detalle->diferencia < 0 ? 'text-red-300' : 'text-white/50') }}">
                                {{ $detalle->diferencia > 0 ? '+' : ($detalle->diferencia < 0 ? '−' : '') }}{{ $fmt(abs($dif), $u) }} {{ $u }}
                            </td>
                            <td class="py-2.5 text-white/40">{{ $detalle->motivo ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if (in_array($conteo->estado, ['borrador', 'completado'], true))
                <p class="text-xs text-white/40 mt-4">
                    <i class="fas fa-circle-info mr-1"></i>
                    Al aprobar, cada diferencia genera un ajuste en el kardex (positivo si contaste más, negativo si contaste menos).
                    Las líneas con diferencia cero no generan movimiento.
                </p>
            @endif
        @endif
    </div>
@endsection

@if ($editable)
    @push('scripts')
    <script>
    (function () {
        const articulos = @json($articulos->keyBy('id'));
        const filasIniciales = @json($filasIniciales);
        const rows = document.getElementById('detalleRows');
        const template = document.getElementById('filaTemplate');
        let indice = 0;

        const formato = (valor, unidad) => Number(valor).toLocaleString('es-CO', {
            maximumFractionDigits: unidad === 'kg' ? 3 : 0,
        });

        function actualizar(row) {
            const articulo = articulos[row.querySelector('.item-select').value];
            const stockEl = row.querySelector('.stock-sistema');
            const difEl = row.querySelector('.diferencia');
            const unidadEl = row.querySelector('.unidad');
            const input = row.querySelector('.cantidad-fisica');

            if (!articulo) {
                stockEl.textContent = '—';
                difEl.textContent = '—';
                difEl.className = 'input-glass bg-gray-900/50 font-semibold diferencia text-white/50';
                unidadEl.textContent = '';
                return;
            }

            unidadEl.textContent = '(' + articulo.unidad + ')';
            input.step = articulo.unidad === 'kg' ? '0.001' : '1';
            stockEl.textContent = formato(articulo.stock, articulo.unidad) + ' ' + articulo.unidad;

            if (input.value === '') {
                difEl.textContent = '—';
                difEl.className = 'input-glass bg-gray-900/50 font-semibold diferencia text-white/50';
                return;
            }

            const dif = Math.round((parseFloat(input.value) - articulo.stock) * 1000) / 1000;
            difEl.textContent = (dif > 0 ? '+' : dif < 0 ? '−' : '') + formato(Math.abs(dif), articulo.unidad) + ' ' + articulo.unidad;
            difEl.className = 'input-glass bg-gray-900/50 font-semibold diferencia '
                + (dif > 0 ? 'text-green-300' : dif < 0 ? 'text-red-300' : 'text-white/50');
        }

        function agregarFila(datos) {
            const row = template.content.firstElementChild.cloneNode(true);
            const i = indice++;

            // Cada fila usa su propio índice para que PHP agrupe sus campos en una sola línea
            row.querySelectorAll('[data-name]').forEach((el) => {
                el.name = 'detalles[' + i + '][' + el.dataset.name + ']';
            });

            const select = row.querySelector('.item-select');
            const input = row.querySelector('.cantidad-fisica');

            select.value = datos.id ? String(datos.id) : '';
            input.value = datos.cantidad ?? '';
            row.querySelector('.motivo').value = datos.motivo ?? '';

            select.addEventListener('change', () => {
                const articulo = articulos[select.value];
                // Sugerir el stock del sistema como punto de partida
                if (articulo && input.value === '') {
                    input.value = articulo.stock;
                }
                actualizar(row);
            });
            input.addEventListener('input', () => actualizar(row));
            row.querySelector('.remove-row').addEventListener('click', () => row.remove());

            rows.appendChild(row);
            actualizar(row);
        }

        document.getElementById('addRowBtn').addEventListener('click', () => agregarFila({}));

        if (filasIniciales.length) {
            filasIniciales.forEach(agregarFila);
        } else {
            agregarFila({});
        }
    })();
    </script>
    @endpush
@endif

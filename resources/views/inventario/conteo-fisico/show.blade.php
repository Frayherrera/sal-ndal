@extends('layouts.app')

@section('title', 'Conteo ' . $conteo->codigo)

@section('content')
@php
    $esMateria = $conteo->tipo === 'materia_prima';
    $editable = in_array($conteo->estado, ['borrador', 'completado'], true);
    $materiasArr = $esMateria
        ? \App\Models\MateriaPrima::where('activo', true)->with('inventario')->get()
            ->map(fn ($m) => ['id' => $m->id, 'nombre' => $m->nombre, 'stock' => $m->stock_gramos()])
            ->values()
        : [];
    $productosArr = !$esMateria
        ? \App\Models\ProductoTerminado::where('activo', true)->with('inventario')->get()
            ->map(fn ($p) => ['id' => $p->id, 'nombre' => $p->nombre, 'stock' => $p->stock_disponible()])
            ->values()
        : [];
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

    <!-- Workflow actions -->
    @if ($conteo->estado === 'borrador')
        <div class="glass-card rounded-2xl p-4 mb-6 flex flex-wrap gap-3 animate-delay-100">
            <form method="POST" action="{{ route('inventario.conteo-fisico.completar', $conteo) }}"
                  onsubmit="return confirm('¿Completar el conteo? No podrás modificar los detalles.')">
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
        </div>
    @endif

    @if ($conteo->estado === 'completado')
        <div class="glass-card rounded-2xl p-4 mb-6 flex flex-wrap gap-3 animate-delay-100">
            <form method="POST" action="{{ route('inventario.conteo-fisico.aprobar', $conteo) }}"
                  onsubmit="return confirm('¿Aprobar el conteo? Se generarán los ajustes de inventario automáticamente.')">
                @csrf
                <button type="submit" class="flex items-center gap-2 bg-green-500/20 text-green-300 px-4 py-2.5 rounded-xl font-medium hover:bg-green-500/30 transition-colors">
                    <i class="fas fa-check-circle"></i> Aprobar y Generar Ajustes
                </button>
            </form>
        </div>
    @endif

    <!-- Details entry -->
    @if ($editable)
        <div class="glass rounded-2xl p-8 mb-6 animate-delay-100">
            <h2 class="text-lg font-semibold text-white mb-2">Registrar Detalles</h2>
            <p class="text-white/50 text-sm mb-6">Ingresa la cantidad contada físicamente. La diferencia se calcula automáticamente.</p>

            <form method="POST" action="{{ route('inventario.conteo-fisico.detalles', $conteo) }}">
                @csrf

                <div class="space-y-3" id="detalleRows">
                    @if ($conteo->detalles->isEmpty())
                        <div class="hidden"></div>
                    @else
                        @foreach ($conteo->detalles as $detalle)
                            @include('inventario.conteo-fisico._detalle-row', [
                                'detalle' => $detalle,
                                'esMateria' => $esMateria,
                                'materiasPrimas' => $esMateria ? \App\Models\MateriaPrima::where('activo', true)->with('inventario')->get() : collect(),
                                'productos' => !$esMateria ? \App\Models\ProductoTerminado::where('activo', true)->with('inventario')->get() : collect(),
                            ])
                        @endforeach
                    @endif
                </div>

                <button type="button" id="addRowBtn"
                        class="mt-4 flex items-center gap-2 text-blue-300 hover:text-blue-200 text-sm font-medium">
                    <i class="fas fa-plus"></i> Agregar detalle
                </button>

                <div class="mt-6">
                    <button type="submit"
                            class="flex items-center gap-2 bg-gradient-to-r from-blue-500 to-purple-600 text-white px-6 py-3 rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all shadow-lg shadow-blue-500/30">
                        <i class="fas fa-save"></i> Guardar Detalles
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Detalles list -->
    <div class="glass-card rounded-2xl p-6 animate-delay-200 overflow-x-auto">
        <div class="flex items-center justify-between mb-4">
            <h2 class="text-lg font-semibold text-white">Líneas del Conteo</h2>
            <span class="text-xs text-white/40">{{ $conteo->detalles->count() }} líneas</span>
        </div>

        @if ($conteo->detalles->isEmpty())
            <div class="text-center py-12">
                <i class="fas fa-clipboard-list text-3xl text-white/20 mb-3"></i>
                <p class="text-white/50 text-sm">Aún no hay líneas registradas.</p>
            </div>
        @else
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-white/50 border-b border-white/10">
                        <th class="py-2 pr-4">Artículo</th>
                        <th class="py-2 pr-4 text-right">Stock sistema</th>
                        <th class="py-2 pr-4 text-right">Stock físico</th>
                        <th class="py-2 pr-4 text-right">Diferencia</th>
                        <th class="py-2">Motivo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($conteo->detalles as $detalle)
                        @php
                            $nombre = $detalle->materiaPrima?->nombre ?? $detalle->productoTerminado?->nombre ?? 'N/D';
                            $unidad = $detalle->materiaPrima ? 'g' : 'u';
                        @endphp
                        <tr class="border-b border-white/5 hover:bg-white/5 transition-colors">
                            <td class="py-2.5 pr-4 text-white font-medium">{{ $nombre }}</td>
                            <td class="py-2.5 pr-4 text-right text-white/60">{{ number_format($detalle->stock_sistema, 0) }} {{ $unidad }}</td>
                            <td class="py-2.5 pr-4 text-right text-white">{{ number_format($detalle->cantidad_fisica, 0) }} {{ $unidad }}</td>
                            <td class="py-2.5 pr-4 text-right font-semibold {{ $detalle->diferencia > 0 ? 'text-green-300' : ($detalle->diferencia < 0 ? 'text-red-300' : 'text-white/50') }}">
                                {{ $detalle->diferencia > 0 ? '+' : '' }}{{ number_format($detalle->diferencia, 0) }}
                            </td>
                            <td class="py-2.5 text-white/40">{{ $detalle->motivo ?? '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection

@if ($editable)
    @push('scripts')
    <script>
    (function () {
        const rows = document.getElementById('detalleRows');
        const addBtn = document.getElementById('addRowBtn');
        const esMateria = @json($esMateria);

        const materias = @json($materiasArr);
        const productos = @json($productosArr);

        function rowHtml(itemId, stock, nombre) {
            const options = (esMateria ? materias : productos).map(function (o) {
                const sel = o.id == itemId ? 'selected' : '';
                return '<option value="' + o.id + '" data-stock="' + o.stock + '" ' + sel + '>' + o.nombre + '</option>';
            }).join('');

            return '<div class="glass-card rounded-xl p-4 detail-row">' +
                '<div class="flex flex-wrap gap-3 items-end">' +
                '<div class="flex-1 min-w-[200px]">' +
                '<label class="block text-xs text-white/50 mb-1">' + (esMateria ? 'Materia prima' : 'Producto') + '</label>' +
                '<select name="detalles[][' + (esMateria ? 'materia_prima_id' : 'producto_terminado_id') + ']" class="input-glass bg-gray-900/60 item-select">' +
                '<option value="">Seleccionar...</option>' + options + '</select></div>' +
                '<div class="w-28">' +
                '<label class="block text-xs text-white/50 mb-1">Stock sistema</label>' +
                '<input type="number" class="input-glass bg-gray-900/50 stock-sistema" value="' + (stock || 0) + '" readonly></div>' +
                '<div class="w-28">' +
                '<label class="block text-xs text-white/50 mb-1">Stock físico</label>' +
                '<input type="number" min="0" name="detalles[][cantidad_fisica]" class="input-glass cantidad-fisica" value="' + stock + '"></div>' +
                '<div class="flex-1 min-w-[200px]">' +
                '<label class="block text-xs text-white/50 mb-1">Motivo (si hay diferencia)</label>' +
                '<input type="text" name="detalles[][motivo]" class="input-glass motivo" placeholder="Ej: merma, sobrante"></div>' +
                '<button type="button" class="w-10 h-10 flex items-center justify-center rounded-lg bg-red-500/20 text-red-300 hover:bg-red-500/30 remove-row"><i class="fas fa-trash"></i></button>' +
                '</div></div>';
        }

        function bindListeners(row) {
            const sel = row.querySelector('.item-select');
            if (sel) {
                sel.addEventListener('change', function () {
                    const opt = sel.options[sel.selectedIndex];
                    const nro = row.querySelector('.stock-sistema');
                    const cf = row.querySelector('.cantidad-fisica');
                    if (opt && opt.dataset.stock !== undefined) {
                        nro.value = opt.dataset.stock;
                        cf.value = opt.dataset.stock;
                    } else {
                        nro.value = '0';
                        cf.value = '0';
                    }
                });
            }
            row.querySelector('.remove-row').addEventListener('click', function () {
                row.remove();
            });
        }

        if (rows && rows.querySelector('.detail-row')) {
            rows.querySelectorAll('.detail-row').forEach(bindListeners);
        }

        addBtn.addEventListener('click', function () {
            const div = document.createElement('div');
            div.innerHTML = rowHtml('', 0, '');
            const row = div.firstElementChild;
            rows.appendChild(row);
            bindListeners(row);
        });
    })();
    </script>
    @endpush
@endif

@extends('reportes.pdf.layout')

@php
    use App\Services\ReporteInventarioService;
    use App\Support\Paquetes;

    $p = fn ($u) => Paquetes::texto((int) $u);
    $colorEstado = [
        'agotado' => ['#fee2e2', '#991b1b'],
        'bajo' => ['#fef3c7', '#92400e'],
        'ok' => ['#dcfce7', '#166534'],
    ];
    // Encabezados de grupo y subtotales solo si hay categorías reales
    $mostrarGrupos = count($grupos) > 1 || ($grupos[0]['categoria'] ?? '') !== 'Sin categoría';
@endphp

@section('contenido')
    {{-- Resumen --}}
    <h2>Resumen</h2>
    <table class="kpis">
        <tr>
            <td>
                <div class="kpi principal">
                    <div class="etq">Total producto</div>
                    <div class="val">{{ $p($kpis['total']) }}</div>
                    <div class="sub">{{ $kpis['articulos'] }} productos · bodega + camión</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">En bodega</div>
                    <div class="val">{{ $p($kpis['bodega']) }}</div>
                    <div class="sub">disponible para surtir</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">En el camión</div>
                    <div class="val">{{ $p($kpis['camion']) }}</div>
                    <div class="sub">{{ $kpis['camion'] > 0 ? 'cargado o de regreso' : 'camión vacío' }}</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">Bajo mínimo / agotados</div>
                    <div class="val"><span class="ambar">{{ $kpis['bajos'] }}</span> / <span class="rojo">{{ $kpis['agotados'] }}</span></div>
                    <div class="sub">según el stock de bodega</div>
                </div>
            </td>
        </tr>
    </table>

    @include('reportes.pdf._estados', ['kpis' => $kpis, 'nombre' => 'productos'])

    {{-- Inventario por categoría --}}
    <h2>Inventario actual</h2>
    @if ($grupos === [])
        <div class="vacio">No hay productos registrados.</div>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th style="width: 58px;">Código</th>
                    <th>Producto</th>
                    <th class="right" style="width: 66px;">Bodega</th>
                    <th class="right" style="width: 62px;">Camión</th>
                    <th class="right" style="width: 66px;">Total</th>
                    <th class="right" style="width: 56px;">Mínimo</th>
                    <th class="center" style="width: 60px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grupos as $grupo)
                    @if ($mostrarGrupos)
                    <tr>
                        <td colspan="7" style="background: #eef2ff; color: #3730a3; font-weight: bold; padding-top: 7px;">
                            {{ $grupo['categoria'] }}
                            <span class="muted" style="font-weight: normal;">
                                · {{ count($grupo['filas']) }} productos
                                @if ($grupo['agotados']) · <span class="rojo">{{ $grupo['agotados'] }} agotados</span>@endif
                                @if ($grupo['bajos']) · <span class="ambar">{{ $grupo['bajos'] }} bajo mínimo</span>@endif
                            </span>
                        </td>
                    </tr>
                    @endif
                    @foreach ($grupo['filas'] as $f)
                        @php [$fondo, $texto] = $colorEstado[$f['estado']]; @endphp
                        <tr>
                            <td class="num nowrap">{{ $f['codigo'] }}</td>
                            <td>
                                {{ $f['nombre'] }}
                                @if ($f['presentacion'])<span class="muted"> · {{ $f['presentacion'] }}</span>@endif
                                @if (! $f['activo'])<span class="muted"> · inactivo</span>@endif
                            </td>
                            <td class="right nowrap {{ $f['estado'] === 'agotado' ? 'rojo' : '' }}">{{ $p($f['bodega']) }}</td>
                            <td class="right nowrap {{ $f['camion'] > 0 ? '' : 'muted' }}">{{ $f['camion'] > 0 ? $p($f['camion']) : '—' }}</td>
                            <td class="right nowrap"><b>{{ $p($f['total']) }}</b></td>
                            <td class="right nowrap muted">{{ $f['minimo'] > 0 ? $p($f['minimo']) : '—' }}</td>
                            <td class="center"><span class="badge" style="background: {{ $fondo }}; color: {{ $texto }};">{{ ReporteInventarioService::ESTADOS[$f['estado']] }}</span></td>
                        </tr>
                    @endforeach
                    @if ($mostrarGrupos)
                    <tr>
                        <td colspan="2" class="right muted" style="font-size: 7.5px;">Subtotal {{ $grupo['categoria'] }}</td>
                        <td class="right nowrap" style="font-weight: bold;">{{ $p($grupo['subtotal']['bodega']) }}</td>
                        <td class="right nowrap" style="font-weight: bold;">{{ $p($grupo['subtotal']['camion']) }}</td>
                        <td class="right nowrap" style="font-weight: bold;">{{ $p($grupo['subtotal']['total']) }}</td>
                        <td colspan="2"></td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="2">Total ({{ $kpis['articulos'] }} productos)</td>
                    <td class="right nowrap">{{ $p($kpis['bodega']) }}</td>
                    <td class="right nowrap">{{ $p($kpis['camion']) }}</td>
                    <td class="right nowrap">{{ $p($kpis['total']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="filtros" style="margin-top: 6px;">
        Cantidades en paquetes de {{ Paquetes::UNIDADES }} (p) y unidades sueltas (u).
        El estado se calcula con el stock de bodega.
        @if ($kpis['inactivos'] > 0)
            Se incluyen {{ $kpis['inactivos'] }} productos inactivos porque todavía tienen stock.
        @endif
    </div>
@endsection

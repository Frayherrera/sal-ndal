@extends('reportes.pdf.layout')

@php
    use App\Services\ReporteInventarioService;

    $kg = fn ($v) => rtrim(rtrim(number_format((float) $v, 3, ',', '.'), '0'), ',').' kg';
    $pct = fn ($parte, $todo) => $todo > 0 ? round($parte / $todo * 100, 1) : 0;
    $colorEstado = [
        'agotado' => ['#fee2e2', '#991b1b'],
        'bajo' => ['#fef3c7', '#92400e'],
        'ok' => ['#dcfce7', '#166534'],
    ];
    $todas = collect($grupos)->flatMap(fn ($g) => $g['filas']);
    // Columnas y agrupación solo cuando aportan información
    $conProveedor = $todas->contains(fn ($f) => filled($f['proveedor']) || filled($f['ubicacion']));
    $mostrarGrupos = count($grupos) > 1 || ($grupos[0]['categoria'] ?? '') !== 'Sin categoría';
    $columnas = $conProveedor ? 6 : 5;
    $antesDeStock = $conProveedor ? 3 : 2;
@endphp

@section('contenido')
    {{-- Resumen --}}
    <h2>Resumen</h2>
    <table class="kpis">
        <tr>
            <td>
                <div class="kpi principal">
                    <div class="etq">Materias primas</div>
                    <div class="val">{{ $kpis['articulos'] }}</div>
                    <div class="sub">{{ count($grupos) }} {{ count($grupos) === 1 ? 'categoría' : 'categorías' }}</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">Stock en bodega</div>
                    <div class="val">{{ $kg($kpis['total_kg']) }}</div>
                    <div class="sub">suma de todas</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">Bajo mínimo</div>
                    <div class="val ambar">{{ $kpis['bajos'] }}</div>
                    <div class="sub">por reponer pronto</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">Agotadas</div>
                    <div class="val rojo">{{ $kpis['agotados'] }}</div>
                    <div class="sub">sin stock</div>
                </div>
            </td>
        </tr>
    </table>

    @include('reportes.pdf._estados', ['kpis' => $kpis, 'nombre' => 'materias primas'])

    {{-- Inventario por categoría --}}
    <h2>Inventario actual</h2>
    @if ($grupos === [])
        <div class="vacio">No hay materias primas registradas.</div>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th style="width: 58px;">Código</th>
                    <th>Materia prima</th>
                    @if ($conProveedor)<th style="width: 150px;">Proveedor · Ubicación</th>@endif
                    <th class="right" style="width: 72px;">Stock</th>
                    <th class="right" style="width: 62px;">Mínimo</th>
                    <th class="center" style="width: 62px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($grupos as $grupo)
                    @if ($mostrarGrupos)
                    <tr>
                        <td colspan="{{ $columnas }}" style="background: #eef2ff; color: #3730a3; font-weight: bold; padding-top: 7px;">
                            {{ $grupo['categoria'] }}
                            <span class="muted" style="font-weight: normal;">
                                · {{ count($grupo['filas']) }} artículos
                                @if ($grupo['agotados']) · <span class="rojo">{{ $grupo['agotados'] }} agotadas</span>@endif
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
                                @if ($f['molido'])<span class="muted"> · molido</span>@endif
                                @if (! $f['activo'])<span class="muted"> · inactiva</span>@endif
                            </td>
                            @if ($conProveedor)<td class="muted">{{ collect([$f['proveedor'], $f['ubicacion']])->filter()->implode(' · ') ?: '—' }}</td>@endif
                            <td class="right nowrap {{ $f['estado'] === 'agotado' ? 'rojo' : '' }}"><b>{{ $kg($f['stock']) }}</b></td>
                            <td class="right nowrap muted">{{ $f['minimo'] > 0 ? $kg($f['minimo']) : '—' }}</td>
                            <td class="center"><span class="badge" style="background: {{ $fondo }}; color: {{ $texto }};">{{ ReporteInventarioService::ESTADOS[$f['estado']] }}</span></td>
                        </tr>
                    @endforeach
                    @if ($mostrarGrupos)
                    <tr>
                        <td colspan="{{ $antesDeStock }}" class="right muted" style="font-size: 7.5px;">Subtotal {{ $grupo['categoria'] }}</td>
                        <td class="right nowrap" style="font-weight: bold;">{{ $kg($grupo['subtotal']['stock']) }}</td>
                        <td colspan="2"></td>
                    </tr>
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="{{ $antesDeStock }}">Total ({{ $kpis['articulos'] }} materias primas)</td>
                    <td class="right nowrap">{{ $kg($kpis['total_kg']) }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>
    @endif

    @if ($kpis['inactivos'] > 0)
        <div class="filtros" style="margin-top: 6px;">Se incluyen {{ $kpis['inactivos'] }} materias primas inactivas porque todavía tienen stock.</div>
    @endif
@endsection

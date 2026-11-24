@extends('reportes.pdf.layout')

@php
    use App\Models\Gasto;

    $dinero = fn ($v) => '$'.number_format((float) $v, 0, ',', '.');
    $pct = fn ($parte, $todo) => $todo > 0 ? round($parte / $todo * 100) : 0;
    $registradosDetalle = $detalle->where('estado', Gasto::ESTADO_REGISTRADO);
@endphp

@section('contenido')
    {{-- Resumen --}}
    <h2>Resumen</h2>
    <table class="kpis">
        <tr>
            <td>
                <div class="kpi principal">
                    <div class="etq">Total gastado</div>
                    <div class="val">{{ $dinero($kpis['total']) }}</div>
                    <div class="sub">{{ $kpis['count'] }} {{ $kpis['count'] === 1 ? 'gasto' : 'gastos' }} · {{ $kpis['categorias_count'] }} categorías</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">Gastos operativos</div>
                    <div class="val">{{ $dinero($kpis['operativos_total']) }}</div>
                    <div class="sub">{{ $kpis['operativos_count'] }} registrados a mano</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">Compras de materia prima</div>
                    <div class="val">{{ $dinero($kpis['compras_total']) }}</div>
                    <div class="sub">{{ $kpis['compras_count'] }} desde inventario</div>
                </div>
            </td>
            <td>
                <div class="kpi">
                    <div class="etq">Gasto promedio</div>
                    <div class="val">{{ $dinero($kpis['promedio']) }}</div>
                    <div class="sub">por registro</div>
                </div>
            </td>
        </tr>
    </table>

    {{-- Operativos vs compras --}}
    @php $pOperativos = $pct($kpis['operativos_total'], $kpis['total']); @endphp
    <table style="margin-top: 2px;">
        <tr>
            <td style="width: 50%; padding-right: 8px;">
                <span style="color:#4f46e5; font-weight:bold;">■</span> Operativos:
                <b>{{ $dinero($kpis['operativos_total']) }}</b>
                <span class="muted">({{ $pOperativos }}%)</span>
            </td>
            <td style="width: 50%;" class="right">
                <span style="color:#a78bfa; font-weight:bold;">■</span> Compras de materia prima:
                <b>{{ $dinero($kpis['compras_total']) }}</b>
                <span class="muted">({{ $kpis['total'] > 0 ? 100 - $pOperativos : 0 }}%)</span>
            </td>
        </tr>
    </table>
    @if ($kpis['total'] > 0)
        <table class="mezcla" style="margin-top: 5px;">
            <tr>
                @if ($pOperativos > 0)<td style="width: {{ $pOperativos }}%; background: #4f46e5;"></td>@endif
                @if ($pOperativos < 100)<td style="background: #a78bfa;"></td>@endif
            </tr>
        </table>
    @endif
    <div class="filtros" style="margin-top: 6px;">
        @if ($kpis['mayor_descripcion'])
            Mayor gasto: <b style="color:#1e293b;">{{ $kpis['mayor_descripcion'] }}</b> por {{ $dinero($kpis['mayor_monto']) }}.
        @endif
        @if ($kpis['anulados_count'] > 0)
            No se incluyen {{ $kpis['anulados_count'] }} {{ $kpis['anulados_count'] === 1 ? 'gasto anulado' : 'gastos anulados' }} en los totales.
        @endif
    </div>

    {{-- Por categoría --}}
    <h2>Gastos por categoría</h2>
    @if ($por_categoria === [])
        <div class="vacio">No hay gastos en el periodo.</div>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th style="width: 22px;">#</th>
                    <th>Categoría</th>
                    <th class="right" style="width: 50px;">Gastos</th>
                    <th style="width: 190px;">Participación</th>
                    <th class="right" style="width: 90px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($por_categoria as $i => $c)
                    <tr>
                        <td><span class="rank">{{ $i + 1 }}</span></td>
                        <td>
                            <b>{{ $c['categoria'] }}</b>
                            @if ($c['automatica'])<span class="muted"> · automática</span>@endif
                        </td>
                        <td class="right">{{ $c['count'] }}</td>
                        <td>
                            <table>
                                <tr>
                                    <td style="padding: 0 6px 0 0; border: none; background: transparent;">
                                        <div class="part-pista" style="height: 7px; margin: 0;"><div class="part-barra" style="height: 7px; width: {{ $c['participacion'] }}%;"></div></div>
                                    </td>
                                    <td class="right muted" style="width: 38px; padding: 0; border: none; background: transparent;">{{ number_format($c['participacion'], 1, ',', '.') }}%</td>
                                </tr>
                            </table>
                        </td>
                        <td class="right"><b>{{ $dinero($c['total']) }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Por día / semana --}}
    <h2>Gastos por {{ $por_periodo['agrupacion'] === 'semana' ? 'semana' : 'día' }}</h2>
    @if ($por_periodo['filas'] === [])
        <div class="vacio">No hay gastos en el periodo.</div>
    @else
        <table class="barras">
            @foreach ($por_periodo['filas'] as $fila)
                @php $ancho = $por_periodo['maximo'] > 0 ? max(round($fila['total'] / $por_periodo['maximo'] * 100, 1), $fila['total'] > 0 ? 1.5 : 0) : 0; @endphp
                <tr>
                    <td class="etq nowrap">{{ $fila['etiqueta'] }}</td>
                    <td style="padding-right: 8px;">
                        <div class="pista">
                            <div class="barra {{ $ancho == 0 ? 'cero' : '' }}" style="width: {{ $ancho }}%; background: #8b5cf6;"></div>
                        </div>
                    </td>
                    <td class="val nowrap {{ $fila['total'] == 0 ? 'muted' : '' }}">{{ $fila['total'] > 0 ? $dinero($fila['total']) : '—' }}</td>
                    <td class="cant nowrap">{{ $fila['count'] > 0 ? $fila['count'].' '.($fila['count'] === 1 ? 'gasto' : 'gastos') : '' }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    {{-- Proveedores --}}
    @if ($top_proveedores !== [])
        <h2>Principales proveedores</h2>
        <table class="tabla">
            <thead>
                <tr>
                    <th style="width: 22px;">#</th>
                    <th>Proveedor / beneficiario</th>
                    <th class="right" style="width: 50px;">Gastos</th>
                    <th class="right" style="width: 110px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($top_proveedores as $i => $p)
                    <tr>
                        <td><span class="rank">{{ $i + 1 }}</span></td>
                        <td><b>{{ $p['proveedor'] }}</b></td>
                        <td class="right">{{ $p['count'] }}</td>
                        <td class="right"><b>{{ $dinero($p['total']) }}</b></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    {{-- Detalle --}}
    <h2 class="salto">Detalle de gastos</h2>
    @if ($detalle->isEmpty())
        <div class="vacio">No hay gastos con estos filtros.</div>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th style="width: 62px;">Número</th>
                    <th style="width: 50px;">Fecha</th>
                    <th>Descripción</th>
                    <th style="width: 92px;">Categoría</th>
                    <th style="width: 58px;">Pago</th>
                    <th class="right" style="width: 74px;">Valor</th>
                    <th class="center" style="width: 58px;">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($detalle as $g)
                    @php $anulado = $g->estaAnulado(); @endphp
                    <tr>
                        <td class="num nowrap">{{ $g->numero }}</td>
                        <td class="nowrap">{{ $g->fecha->format('d/m/Y') }}</td>
                        <td>
                            {{ $g->descripcion }}
                            @if ($g->proveedor)<span class="muted"> · {{ $g->proveedor }}</span>@endif
                        </td>
                        <td>{{ $g->categoria?->nombre ?? '—' }}</td>
                        <td>{{ $g->metodoPago() }}</td>
                        <td class="right nowrap" @if ($anulado) style="text-decoration: line-through; color:#94a3b8;" @endif>{{ $dinero($g->monto) }}</td>
                        <td class="center">
                            @if ($anulado)
                                <span class="badge" style="background: #e2e8f0; color: #475569;">Anulado</span>
                            @elseif ($g->esAutomatico())
                                <span class="badge" style="background: #ede9fe; color: #6d28d9;">Compra</span>
                            @else
                                <span class="badge" style="background: #dcfce7; color: #166534;">Registrado</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">Total ({{ $registradosDetalle->count() }} gastos registrados)</td>
                    <td class="right nowrap">{{ $dinero($registradosDetalle->sum('monto')) }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>

        @if ($detalle_total > $detalle->count())
            <div class="aviso">
                Se muestran los primeros {{ number_format($detalle->count(), 0, ',', '.') }} de {{ number_format($detalle_total, 0, ',', '.') }} gastos.
                Los totales del resumen sí incluyen todos. Para el listado completo usa la exportación CSV o acota el periodo.
            </div>
        @endif
    @endif
@endsection

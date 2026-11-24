@extends('reportes.pdf.layout')

@php
    use App\Models\Venta;

    $dinero = fn ($v) => '$'.number_format((float) $v, 0, ',', '.');
    $pct = fn ($parte, $todo) => $todo > 0 ? round($parte / $todo * 100) : 0;
    $colorEstado = [
        'pagada' => ['#dcfce7', '#166534'],
        'pendiente' => ['#fef3c7', '#92400e'],
        'vencida' => ['#fee2e2', '#991b1b'],
        'anulada' => ['#e2e8f0', '#475569'],
    ];
    $confirmadasDetalle = $detalle->where('estado', Venta::ESTADO_CONFIRMADA);
    $totalesDetalle = [
        'total' => $confirmadasDetalle->sum('total'),
        'pagado' => $confirmadasDetalle->sum(fn ($v) => $v->totalPagado()),
    ];
@endphp

@section('contenido')
        {{-- Resumen --}}
        <h2>Resumen</h2>
        <table class="kpis">
            <tr>
                <td>
                    <div class="kpi principal">
                        <div class="etq">Total vendido</div>
                        <div class="val">{{ $dinero($kpis['total']) }}</div>
                        <div class="sub">{{ $kpis['count'] }} {{ $kpis['count'] === 1 ? 'venta' : 'ventas' }} · {{ $kpis['clientes_count'] }} clientes</div>
                    </div>
                </td>
                <td>
                    <div class="kpi">
                        <div class="etq">Cobrado</div>
                        <div class="val verde">{{ $dinero($kpis['pagado']) }}</div>
                        <div class="sub">{{ $pct($kpis['pagado'], $kpis['total']) }}% de lo vendido</div>
                    </div>
                </td>
                <td>
                    <div class="kpi">
                        <div class="etq">Saldo pendiente</div>
                        <div class="val ambar">{{ $dinero($kpis['saldo']) }}</div>
                        <div class="sub">{{ $kpis['vencidas_count'] }} {{ $kpis['vencidas_count'] === 1 ? 'venta vencida' : 'ventas vencidas' }}</div>
                    </div>
                </td>
                <td>
                    <div class="kpi">
                        <div class="etq">Ticket promedio</div>
                        <div class="val">{{ $dinero($kpis['ticket_promedio']) }}</div>
                        <div class="sub">por venta</div>
                    </div>
                </td>
            </tr>
        </table>

        {{-- Contado vs crédito --}}
        @php $pContado = $pct($kpis['contado_total'], $kpis['total']); @endphp
        <table style="margin-top: 2px;">
            <tr>
                <td style="width: 50%; padding-right: 8px;">
                    <span style="color:#4f46e5; font-weight:bold;">■</span> Contado:
                    <b>{{ $dinero($kpis['contado_total']) }}</b>
                    <span class="muted">({{ $kpis['contado_count'] }} ventas · {{ $pContado }}%)</span>
                </td>
                <td style="width: 50%;" class="right">
                    <span style="color:#a78bfa; font-weight:bold;">■</span> Crédito:
                    <b>{{ $dinero($kpis['credito_total']) }}</b>
                    <span class="muted">({{ $kpis['credito_count'] }} ventas · {{ $kpis['total'] > 0 ? 100 - $pContado : 0 }}%)</span>
                </td>
            </tr>
        </table>
        @if ($kpis['total'] > 0)
            <table class="mezcla" style="margin-top: 5px;">
                <tr>
                    @if ($pContado > 0)<td style="width: {{ $pContado }}%; background: #4f46e5;"></td>@endif
                    @if ($pContado < 100)<td style="background: #a78bfa;"></td>@endif
                </tr>
            </table>
        @endif
        @if ($kpis['anuladas_count'] > 0)
            <div class="filtros" style="margin-top: 6px;">
                No se incluyen {{ $kpis['anuladas_count'] }} {{ $kpis['anuladas_count'] === 1 ? 'venta anulada' : 'ventas anuladas' }} en los totales.
            </div>
        @endif

        {{-- Ventas por día / semana --}}
        <h2>Ventas por {{ $por_periodo['agrupacion'] === 'semana' ? 'semana' : 'día' }}</h2>
        @if ($por_periodo['filas'] === [])
            <div class="vacio">No hay ventas en el periodo.</div>
        @else
            <table class="barras">
                @foreach ($por_periodo['filas'] as $fila)
                    @php $ancho = $por_periodo['maximo'] > 0 ? max(round($fila['total'] / $por_periodo['maximo'] * 100, 1), $fila['total'] > 0 ? 1.5 : 0) : 0; @endphp
                    <tr>
                        <td class="etq nowrap">{{ $fila['etiqueta'] }}</td>
                        <td style="padding-right: 8px;">
                            <div class="pista">
                                <div class="barra {{ $ancho == 0 ? 'cero' : '' }}" style="width: {{ $ancho }}%;"></div>
                            </div>
                        </td>
                        <td class="val nowrap {{ $fila['total'] == 0 ? 'muted' : '' }}">{{ $fila['total'] > 0 ? $dinero($fila['total']) : '—' }}</td>
                        <td class="cant nowrap">{{ $fila['count'] > 0 ? $fila['count'].' '.($fila['count'] === 1 ? 'venta' : 'ventas') : '' }}</td>
                    </tr>
                @endforeach
            </table>
        @endif

        {{-- Top clientes --}}
        <div class="bloque">
        <h2>Top {{ count($top_clientes) }} clientes</h2>
        @if ($top_clientes === [])
            <div class="vacio">No hay ventas en el periodo.</div>
        @else
            <table class="tabla">
                <thead>
                    <tr>
                        <th style="width: 22px;">#</th>
                        <th>Cliente</th>
                        <th>Local · Ciudad</th>
                        <th class="right" style="width: 44px;">Ventas</th>
                        <th class="right" style="width: 120px;">Total</th>
                        <th class="right" style="width: 78px;">Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($top_clientes as $i => $c)
                        <tr>
                            <td><span class="rank">{{ $i + 1 }}</span></td>
                            <td><b>{{ $c['cliente'] }}</b></td>
                            <td class="muted">{{ collect([$c['local'], $c['ciudad']])->filter()->implode(' · ') ?: '—' }}</td>
                            <td class="right">{{ $c['count'] }}</td>
                            <td class="right">
                                <b>{{ $dinero($c['total']) }}</b> <span class="muted">{{ number_format($c['participacion'], 1, ',', '.') }}%</span>
                                <div class="part-pista"><div class="part-barra" style="width: {{ $c['participacion'] }}%;"></div></div>
                            </td>
                            <td class="right {{ $c['saldo'] > 0 ? 'ambar' : 'muted' }}">{{ $c['saldo'] > 0 ? $dinero($c['saldo']) : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        </div>

        {{-- Detalle --}}
        <h2>Detalle de ventas</h2>
        @if ($detalle->isEmpty())
            <div class="vacio">No hay ventas con estos filtros.</div>
        @else
            <table class="tabla">
                <thead>
                    <tr>
                        <th style="width: 62px;">Número</th>
                        <th style="width: 50px;">Fecha</th>
                        <th>Cliente</th>
                        <th style="width: 50px;">Pago</th>
                        <th class="right" style="width: 70px;">Total</th>
                        <th class="right" style="width: 70px;">Abonado</th>
                        <th class="right" style="width: 66px;">Saldo</th>
                        <th class="center" style="width: 58px;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($detalle as $v)
                        @php
                            $estado = $v->estadoPago();
                            [$fondo, $texto] = $colorEstado[$estado] ?? ['#e2e8f0', '#475569'];
                            $anulada = $v->estado === Venta::ESTADO_ANULADA;
                        @endphp
                        <tr>
                            <td class="num nowrap">{{ $v->numero }}</td>
                            <td class="nowrap">{{ $v->fecha->format('d/m/Y') }}</td>
                            <td>
                                {{ $v->cliente?->nombre ?? '—' }}
                                @if ($v->cliente?->local)<span class="muted"> · {{ $v->cliente->local }}</span>@endif
                            </td>
                            <td>{{ $v->formaPago() }}</td>
                            <td class="right nowrap" @if ($anulada) style="text-decoration: line-through; color:#94a3b8;" @endif>{{ $dinero($v->total) }}</td>
                            <td class="right nowrap">{{ $anulada ? '—' : $dinero($v->totalPagado()) }}</td>
                            <td class="right nowrap {{ ! $anulada && $v->saldo() > 0 ? 'ambar' : 'muted' }}">{{ ! $anulada && $v->saldo() > 0 ? $dinero($v->saldo()) : '—' }}</td>
                            <td class="center"><span class="badge" style="background: {{ $fondo }}; color: {{ $texto }};">{{ Venta::ESTADO_PAGO_LABELS[$estado] ?? $estado }}</span></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="4">Total ({{ $detalle->where('estado', Venta::ESTADO_CONFIRMADA)->count() }} ventas confirmadas)</td>
                        <td class="right nowrap">{{ $dinero($totalesDetalle['total']) }}</td>
                        <td class="right nowrap">{{ $dinero($totalesDetalle['pagado']) }}</td>
                        <td class="right nowrap">{{ $dinero(max($totalesDetalle['total'] - $totalesDetalle['pagado'], 0)) }}</td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>

            @if ($detalle_total > $detalle->count())
                <div class="aviso">
                    Se muestran las primeras {{ number_format($detalle->count(), 0, ',', '.') }} de {{ number_format($detalle_total, 0, ',', '.') }} ventas.
                    Los totales del resumen sí incluyen todas. Para el listado completo usa la exportación CSV o acota el periodo.
                </div>
            @endif
        @endif

@endsection

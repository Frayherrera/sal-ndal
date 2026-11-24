<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de ventas — Santini</title>
    @php
        use App\Models\Venta;

        $dinero = fn ($v) => '$'.number_format((float) $v, 0, ',', '.');
        $periodo = $desde && $hasta
            ? ($desde->equalTo($hasta) ? $desde->format('d/m/Y') : $desde->format('d/m/Y').' – '.$hasta->format('d/m/Y'))
            : 'Sin ventas en el periodo';
        $pct = fn ($parte, $todo) => $todo > 0 ? round($parte / $todo * 100) : 0;
        $colorEstado = [
            'pagada' => ['#dcfce7', '#166534'],
            'pendiente' => ['#fef3c7', '#92400e'],
            'vencida' => ['#fee2e2', '#991b1b'],
            'anulada' => ['#e2e8f0', '#475569'],
        ];
        $totalesDetalle = [
            'total' => $detalle->where('estado', Venta::ESTADO_CONFIRMADA)->sum('total'),
            'pagado' => $detalle->where('estado', Venta::ESTADO_CONFIRMADA)->sum(fn ($v) => $v->totalPagado()),
        ];
    @endphp
    <style>
        @page { margin: 34px 36px 52px 36px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #1e293b; margin: 0; }
        table { border-collapse: collapse; width: 100%; }
        .muted { color: #64748b; }
        .right { text-align: right; }
        .center { text-align: center; }
        .nowrap { white-space: nowrap; }

        /* Encabezado */
        .header { width: 100%; border-bottom: 3px solid #4f46e5; padding-bottom: 12px; margin-bottom: 16px; }
        .header td { vertical-align: middle; }
        .logo { width: 58px; height: 58px; border-radius: 10px; }
        .marca { font-size: 20px; font-weight: bold; letter-spacing: 2px; color: #1e293b; }
        .submarca { font-size: 8.5px; color: #64748b; letter-spacing: .5px; margin-top: 2px; }
        .titulo { font-size: 15px; font-weight: bold; color: #4f46e5; margin-top: 6px; }
        .caja-periodo { background: #eef2ff; border: 1px solid #c7d2fe; border-radius: 8px; padding: 8px 12px; text-align: right; }
        .caja-periodo .etq { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1px; color: #6366f1; }
        .caja-periodo .val { font-size: 11px; font-weight: bold; color: #1e1b4b; margin-top: 2px; }
        .filtros { margin-top: 4px; font-size: 7.5px; color: #64748b; }

        /* Secciones */
        h2 { font-size: 11px; color: #1e293b; margin: 18px 0 8px; padding-left: 7px; border-left: 3px solid #7c3aed; text-transform: uppercase; letter-spacing: .8px; }

        /* KPIs */
        .kpis td { width: 25%; padding: 0 4px 8px 0; vertical-align: top; }
        .kpi { border: 1px solid #e2e8f0; border-radius: 8px; padding: 9px 10px; background: #f8fafc; }
        .kpi .etq { font-size: 7.5px; text-transform: uppercase; letter-spacing: .7px; color: #64748b; }
        .kpi .val { font-size: 14px; font-weight: bold; margin-top: 3px; color: #0f172a; }
        .kpi .sub { font-size: 7.5px; color: #64748b; margin-top: 2px; }
        .kpi.principal { background: #4f46e5; border-color: #4f46e5; }
        .kpi.principal .etq, .kpi.principal .sub { color: #c7d2fe; }
        .kpi.principal .val { color: #ffffff; }
        .verde { color: #15803d !important; }
        .ambar { color: #b45309 !important; }

        /* Mezcla contado / crédito */
        .mezcla { height: 9px; border-radius: 5px; overflow: hidden; }
        .mezcla td { padding: 0; height: 9px; }

        /* Barras por día */
        .barras td { padding: 2.5px 0; vertical-align: middle; }
        .barras .etq { width: 62px; color: #475569; font-size: 8px; }
        .barras .pista { background: #eef2ff; border-radius: 3px; height: 11px; }
        .barras .barra { background: #6366f1; border-radius: 3px; height: 11px; }
        .barras .barra.cero { background: transparent; }
        .barras .val { width: 78px; text-align: right; font-weight: bold; font-size: 8px; }
        .barras .cant { width: 46px; text-align: right; color: #94a3b8; font-size: 7.5px; }

        /* Tablas de datos */
        .tabla th { background: #1e293b; color: #f8fafc; font-size: 7.5px; text-transform: uppercase; letter-spacing: .5px; padding: 6px 5px; text-align: left; }
        .tabla th.right { text-align: right; }
        .tabla td { padding: 5px; border-bottom: 1px solid #edf0f5; }
        .tabla tbody tr:nth-child(even) td { background: #f8fafc; }
        .tabla tr { page-break-inside: avoid; }
        .tabla thead { display: table-header-group; }
        .tabla tfoot td { background: #eef2ff; font-weight: bold; border-top: 2px solid #4f46e5; border-bottom: none; }
        .num { font-family: 'DejaVu Sans Mono', monospace; font-size: 8px; color: #4f46e5; }
        .badge { display: inline-block; padding: 1.5px 6px; border-radius: 8px; font-size: 7px; font-weight: bold; }
        .rank { display: inline-block; width: 15px; height: 15px; line-height: 15px; border-radius: 8px; background: #ede9fe; color: #6d28d9; text-align: center; font-weight: bold; font-size: 7.5px; }
        .part-pista { background: #f1f5f9; height: 5px; border-radius: 3px; margin-top: 2px; }
        .part-barra { background: #a78bfa; height: 5px; border-radius: 3px; }

        .aviso { margin-top: 8px; padding: 7px 10px; background: #fffbeb; border: 1px solid #fde68a; border-radius: 6px; color: #92400e; font-size: 8px; }
        .vacio { padding: 18px; text-align: center; color: #94a3b8; border: 1px dashed #cbd5e1; border-radius: 8px; }
        .salto { page-break-before: always; }
    </style>
</head>
<body>

    {{-- Encabezado --}}
    <table class="header">
        <tr>
            @if ($logo)
                <td style="width: 70px;"><img src="{{ $logo }}" class="logo" alt="Santini"></td>
            @endif
            <td>
                <div class="marca">SANTINI</div>
                <div class="submarca">Condimentos y especias</div>
                <div class="titulo">Reporte de ventas</div>
            </td>
            <td style="width: 210px;">
                <div class="caja-periodo">
                    <div class="etq">Periodo</div>
                    <div class="val">{{ $periodo }}</div>
                    <div class="filtros">
                        Generado {{ $generadoEn->format('d/m/Y H:i') }}@if ($generadoPor) · {{ $generadoPor }}@endif
                    </div>
                    @if ($filtros)
                        <div class="filtros">{{ implode(' · ', $filtros) }}</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

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

    {{-- Detalle --}}
    <h2 class="salto">Detalle de ventas</h2>
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

</body>
</html>

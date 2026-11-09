<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recibo {{ $venta->numero }} — Santini</title>
    <style>
        * { box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            color: #1f2937;
            background: #f1f5f9;
            margin: 0;
            padding: 32px 16px;
            font-size: 13px;
        }
        .sheet {
            background: #fff;
            max-width: 760px;
            margin: 0 auto;
            padding: 36px 40px;
            border-radius: 10px;
            box-shadow: 0 10px 40px rgba(15, 23, 42, 0.12);
            position: relative;
            overflow: hidden;
        }
        .header { display: flex; justify-content: space-between; gap: 24px; border-bottom: 2px solid #1e293b; padding-bottom: 16px; }
        .brand h1 { margin: 0; font-size: 26px; letter-spacing: 1px; color: #1e293b; }
        .brand p { margin: 4px 0 0; color: #64748b; font-size: 12px; }
        .doc { text-align: right; }
        .doc .numero { font-size: 18px; font-weight: 700; font-family: 'Courier New', monospace; color: #1e293b; }
        .doc .fecha { color: #64748b; font-size: 12px; margin-top: 4px; }
        .badge { display: inline-block; margin-top: 8px; padding: 3px 10px; border-radius: 999px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .5px; }
        .badge.pagada { background: #dcfce7; color: #166534; }
        .badge.pendiente { background: #fef9c3; color: #854d0e; }
        .badge.vencida { background: #fee2e2; color: #991b1b; }
        .badge.anulada { background: #e2e8f0; color: #475569; }
        .cliente { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; padding: 18px 0; border-bottom: 1px solid #e2e8f0; }
        .cliente .label { font-size: 10px; text-transform: uppercase; letter-spacing: .6px; color: #94a3b8; margin-bottom: 2px; }
        .cliente .valor { font-weight: 600; color: #1e293b; }
        table { width: 100%; border-collapse: collapse; margin-top: 18px; }
        th { text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: .6px; color: #64748b; border-bottom: 1px solid #cbd5e1; padding: 8px 6px; }
        th.num, td.num { text-align: right; }
        td { padding: 8px 6px; border-bottom: 1px solid #f1f5f9; vertical-align: top; }
        td .codigo { color: #94a3b8; font-size: 10px; font-family: 'Courier New', monospace; }
        .totales { margin-top: 18px; margin-left: auto; width: 280px; }
        .totales .fila { display: flex; justify-content: space-between; padding: 5px 0; }
        .totales .fila.gran-total { border-top: 2px solid #1e293b; margin-top: 6px; padding-top: 10px; font-size: 16px; font-weight: 700; }
        .pagos { margin-top: 26px; border-top: 1px dashed #cbd5e1; padding-top: 14px; }
        .pagos h2 { font-size: 11px; text-transform: uppercase; letter-spacing: .6px; color: #64748b; margin: 0 0 8px; }
        .pagos .fila { display: flex; justify-content: space-between; padding: 3px 0; font-size: 12px; }
        .saldo { margin-top: 10px; padding: 10px 12px; border-radius: 8px; background: #fef9c3; display: flex; justify-content: space-between; font-weight: 700; }
        .saldo.cero { background: #dcfce7; }
        .footer { margin-top: 30px; text-align: center; color: #94a3b8; font-size: 11px; border-top: 1px solid #e2e8f0; padding-top: 14px; }
        .no-print { position: fixed; top: 20px; right: 20px; display: flex; gap: 8px; }
        .btn { border: 0; border-radius: 8px; padding: 10px 16px; font-weight: 600; cursor: pointer; font-size: 13px; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-secondary { background: #e2e8f0; color: #1e293b; }
        .anulada { position: absolute; inset: 0; display: flex; align-items: center; justify-content: center; pointer-events: none; }
        .anulada span { font-size: 90px; font-weight: 800; color: rgba(239, 68, 68, 0.12); transform: rotate(-24deg); letter-spacing: 10px; }
        @media print {
            body { background: #fff; padding: 0; }
            .sheet { box-shadow: none; border-radius: 0; max-width: none; padding: 24px; }
            .no-print { display: none; }
            @page { margin: 12mm; }
        }
    </style>
</head>
<body>
    @php
        $estadoPago = $venta->estadoPago();
        $saldo = $venta->saldo();
    @endphp

    <div class="no-print">
        <a href="{{ route('ventas.show', $venta) }}" class="btn btn-secondary">← Volver</a>
        <button type="button" class="btn btn-primary" onclick="window.print()">Imprimir</button>
    </div>

    <div class="sheet">
        @if ($venta->estado === \App\Models\Venta::ESTADO_ANULADA)
            <div class="anulada"><span>ANULADA</span></div>
        @endif

        <div class="header">
            <div class="brand">
                <h1>SANTINI</h1>
                <p>Condimentos y especias</p>
            </div>
            <div class="doc">
                <div class="numero">{{ $venta->numero }}</div>
                <div class="fecha">{{ $venta->fecha->format('d/m/Y H:i') }}</div>
                <div class="badge {{ $estadoPago }}">
                    {{ \App\Models\Venta::ESTADO_PAGO_LABELS[$estadoPago] ?? $estadoPago }}
                </div>
            </div>
        </div>

        <div class="cliente">
            <div>
                <div class="label">Cliente</div>
                <div class="valor">{{ $venta->cliente?->nombre ?? '—' }}</div>
            </div>
            <div>
                <div class="label">Ciudad / Teléfono</div>
                <div class="valor">{{ $venta->cliente?->ciudad ?? '—' }}</div>
                @if ($venta->cliente?->telefono)
                    <div>{{ $venta->cliente->telefono }}</div>
                @endif
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="num">Unidades</th>
                    <th class="num">Precio unit.</th>
                    <th class="num">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($venta->detalles as $detalle)
                    <tr>
                        <td>
                            {{ $detalle->producto?->nombre ?? 'Producto eliminado' }}
                            <div class="codigo">{{ $detalle->producto?->codigo }}</div>
                        </td>
                        <td class="num">{{ $detalle->cantidad_unidades }}</td>
                        <td class="num">${{ number_format($detalle->precio_unidad, 0, ',', '.') }}</td>
                        <td class="num">{{ number_format($detalle->total, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totales">
            <div class="fila">
                <span>Subtotal</span>
                <span>{{ number_format($venta->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="fila">
                <span>Descuento</span>
                <span>−{{ number_format($venta->descuento_total, 0, ',', '.') }}</span>
            </div>
            <div class="fila gran-total">
                <span>Total</span>
                <span>{{ number_format($venta->total, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="pagos">
            <h2>Abonos</h2>
            @forelse ($venta->pagos->sortBy('fecha') as $pago)
                <div class="fila">
                    <span>
                        {{ $pago->fecha->format('d/m/Y') }} ·
                        {{ \App\Models\PagoVenta::METODOS[$pago->metodo] ?? $pago->metodo }}
                        @if ($pago->referencia)
                            · Ref. {{ $pago->referencia }}
                        @endif
                    </span>
                    <span>{{ number_format($pago->monto, 0, ',', '.') }}</span>
                </div>
            @empty
                <div class="fila"><span>Sin abonos registrados</span><span>$0</span></div>
            @endforelse

            <div class="saldo {{ $saldo <= 0 ? 'cero' : '' }}">
                <span>{{ $saldo > 0 ? 'Saldo pendiente' : 'Pagado en su totalidad' }}</span>
                <span>{{ number_format(max($saldo, 0), 0, ',', '.') }}</span>
            </div>

            @if ($saldo > 0 && $venta->fecha_vencimiento)
                <div class="fila" style="margin-top: 8px; color: #64748b;">
                    <span>Vencimiento del saldo</span>
                    <span>{{ $venta->fecha_vencimiento->format('d/m/Y') }}</span>
                </div>
            @endif
        </div>

        @if ($venta->observaciones)
            <p style="margin-top: 18px; color: #475569;"><strong>Observaciones:</strong> {{ $venta->observaciones }}</p>
        @endif

        <div class="footer">
            Gracias por su compra · {{ $venta->user?->name ? 'Atendido por '.$venta->user->name : 'Santini' }}
        </div>
    </div>
</body>
</html>
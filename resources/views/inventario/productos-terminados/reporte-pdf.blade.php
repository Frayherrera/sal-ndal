@extends('reportes.pdf.layout')

@php
    use App\Models\ProductoTerminado;
    use App\Support\Paquetes;

    $p = fn ($u) => Paquetes::texto((int) $u);
    $colorTexto = ['agotado' => '#b91c1c', 'bajo' => '#b45309', 'ok' => '#111827'];
    $peso = fn ($g) => rtrim(rtrim(number_format((float) $g, 2, ',', '.'), '0'), ',').' g';
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
                    <div class="sub">{{ $kpis['productos'] }} productos · {{ $kpis['articulos'] }} presentaciones</div>
                </div>
            </td>
            @foreach (ProductoTerminado::LINEAS as $linea => $etiqueta)
                <td>
                    <div class="kpi">
                        <div class="etq">{{ $etiqueta }}</div>
                        <div class="val">{{ $p($kpis['por_linea'][$linea] ?? 0) }}</div>
                        <div class="sub">bodega + camión</div>
                    </div>
                </td>
            @endforeach
            <td>
                <div class="kpi">
                    <div class="etq">Bajo mínimo / agotadas</div>
                    <div class="val"><span class="ambar">{{ $kpis['bajos'] }}</span> / <span class="rojo">{{ $kpis['agotados'] }}</span></div>
                    <div class="sub">en el camión: {{ $p($kpis['camion']) }}</div>
                </div>
            </td>
        </tr>
    </table>

    @include('reportes.pdf._estados', ['kpis' => $kpis, 'nombre' => 'presentaciones'])

    {{-- Inventario por producto --}}
    <h2>Inventario actual</h2>
    @if ($filas === [])
        <div class="vacio">No hay productos registrados.</div>
    @else
        <table class="tabla">
            <thead>
                <tr>
                    <th>Producto</th>
                    @foreach (ProductoTerminado::LINEAS as $etiqueta)
                        <th class="right" style="width: 128px;">{{ $etiqueta }}</th>
                    @endforeach
                    <th class="right" style="width: 62px;">Camión</th>
                    <th class="right" style="width: 70px;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($filas as $fila)
                    <tr>
                        <td>
                            {{ $fila['nombre'] }}
                            @if (! $fila['activo'])<span class="muted"> · inactivo</span>@endif
                        </td>
                        @foreach (array_keys(ProductoTerminado::LINEAS) as $linea)
                            @php $deLinea = $fila['lineas'][$linea]['presentaciones']; @endphp
                            <td class="right nowrap">
                                @forelse ($deLinea as $pr)
                                    <div style="color: {{ $colorTexto[$pr['estado']] }};">
                                        @if (count($deLinea) > 1)<span class="muted">{{ $peso($pr['peso']) }}:</span>@endif
                                        {{ $p($pr['total']) }}
                                        @if ($pr['estado'] !== 'ok')
                                            <span style="font-size: 6.5px;">{{ $pr['estado'] === 'agotado' ? '● agotada' : '● bajo' }}</span>
                                        @endif
                                    </div>
                                @empty
                                    <span class="muted">—</span>
                                @endforelse
                            </td>
                        @endforeach
                        <td class="right nowrap {{ $fila['camion'] > 0 ? '' : 'muted' }}">{{ $fila['camion'] > 0 ? $p($fila['camion']) : '—' }}</td>
                        <td class="right nowrap"><b>{{ $p($fila['total']) }}</b></td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td>Total ({{ $kpis['productos'] }} productos)</td>
                    @foreach (array_keys(ProductoTerminado::LINEAS) as $linea)
                        <td class="right nowrap">{{ $p($kpis['por_linea'][$linea] ?? 0) }}</td>
                    @endforeach
                    <td class="right nowrap">{{ $p($kpis['camion']) }}</td>
                    <td class="right nowrap">{{ $p($kpis['total']) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    <div class="filtros" style="margin-top: 6px;">
        Cantidades en paquetes de {{ Paquetes::UNIDADES }} (p) y unidades sueltas (u); cada columna suma bodega + camión.
        El estado de cada presentación se calcula con el stock de bodega
        (<span style="color: #b45309;">● bajo</span> mínimo, <span style="color: #b91c1c;">● agotada</span>).
        @if ($kpis['inactivos'] > 0)
            Se incluyen {{ $kpis['inactivos'] }} presentaciones inactivas porque todavía tienen stock.
        @endif
    </div>
@endsection

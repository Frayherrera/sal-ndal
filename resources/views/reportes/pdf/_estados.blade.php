{{-- Barra con la proporción de artículos OK / bajo mínimo / agotados. Requiere $kpis y $nombre. --}}
@php
    $totalArt = max($kpis['articulos'], 1);
    $partes = [
        ['OK', $kpis['ok'], '#22c55e'],
        ['Bajo mínimo', $kpis['bajos'], '#f59e0b'],
        ['Agotados', $kpis['agotados'], '#ef4444'],
    ];
@endphp
<table style="margin-top: 2px;">
    <tr>
        @foreach ($partes as [$etiqueta, $cantidad, $color])
            <td style="width: 33%;" class="{{ $loop->last ? 'right' : ($loop->first ? '' : 'center') }}">
                <span style="color: {{ $color }}; font-weight: bold;">■</span> {{ $etiqueta }}:
                <b>{{ $cantidad }}</b>
                <span class="muted">({{ round($cantidad / $totalArt * 100) }}%)</span>
            </td>
        @endforeach
    </tr>
</table>
@if ($kpis['articulos'] > 0)
    <table class="mezcla" style="margin-top: 5px;">
        <tr>
            @foreach ($partes as [$etiqueta, $cantidad, $color])
                @if ($cantidad > 0)
                    <td style="width: {{ round($cantidad / $totalArt * 100, 2) }}%; background: {{ $color }};"></td>
                @endif
            @endforeach
        </tr>
    </table>
@endif

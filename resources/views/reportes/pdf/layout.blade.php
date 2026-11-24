<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }} — Santini</title>
    {{-- Diseño común de los reportes PDF (dompdf: CSS 2.1, tablas, sin flexbox ni JS) --}}
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
        .rojo { color: #b91c1c !important; }

        /* Barra de proporción (dos partes) */
        .mezcla { height: 9px; border-radius: 5px; overflow: hidden; }
        .mezcla td { padding: 0; height: 9px; }

        /* Barras por día / categoría */
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
        .tabla th.center { text-align: center; }
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

    <table class="header">
        <tr>
            @if ($logo)
                <td style="width: 70px;"><img src="{{ $logo }}" class="logo" alt="Santini"></td>
            @endif
            <td>
                <div class="marca">SANTINI</div>
                <div class="submarca">Condimentos y especias</div>
                <div class="titulo">{{ $titulo }}</div>
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

    @yield('contenido')

</body>
</html>

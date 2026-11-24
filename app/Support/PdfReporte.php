<?php

namespace App\Support;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;

/**
 * Genera la descarga de un reporte en PDF con el diseño común de Santini
 * (layout `reportes.pdf.layout`): logo, encabezado y pie «Página X de Y».
 */
final class PdfReporte
{
    /**
     * @param  string  $vista  vista que extiende `reportes.pdf.layout`
     * @param  array<string, mixed>  $datos  datos de la vista; si trae `desde`/`hasta` (Carbon) se usan para el periodo
     * @param  array<int, string>  $filtros  filtros aplicados, en texto, para el encabezado
     */
    public static function descargar(string $vista, array $datos, string $titulo, string $prefijoArchivo, array $filtros = []): Response
    {
        $pdf = Pdf::loadView($vista, $datos + [
            'titulo' => $titulo,
            'filtros' => $filtros,
            'periodo' => self::periodo($datos['desde'] ?? null, $datos['hasta'] ?? null),
            'generadoPor' => auth()->user()?->name,
            'generadoEn' => now(),
            'logo' => self::logo(),
        ])->setPaper('letter', 'portrait');

        // Pie de página en todas las hojas
        $pdf->render();
        $dompdf = $pdf->getDomPDF();
        $canvas = $dompdf->getCanvas();
        $fuente = $dompdf->getFontMetrics()->getFont('DejaVu Sans');
        $gris = [0.45, 0.5, 0.58];
        $y = $canvas->get_height() - 28;
        $canvas->page_text(36, $y, "Santini · {$titulo}", $fuente, 7, $gris);
        $canvas->page_text($canvas->get_width() - 110, $y, 'Página {PAGE_NUM} de {PAGE_COUNT}', $fuente, 7, $gris);

        $nombre = $prefijoArchivo.'-'
            .(($datos['desde'] ?? null)?->format('Y-m-d') ?? 'sin-fecha').'_a_'
            .(($datos['hasta'] ?? null)?->format('Y-m-d') ?? 'sin-fecha').'.pdf';

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$nombre.'"',
        ]);
    }

    private static function periodo(?Carbon $desde, ?Carbon $hasta): string
    {
        if (! $desde || ! $hasta) {
            return 'Sin movimientos en el periodo';
        }

        return $desde->equalTo($hasta)
            ? $desde->format('d/m/Y')
            : $desde->format('d/m/Y').' – '.$hasta->format('d/m/Y');
    }

    /** Logo embebido en base64 (dompdf no descarga recursos remotos). */
    private static function logo(): ?string
    {
        $ruta = public_path('logo.jpeg');

        return is_file($ruta) ? 'data:image/jpeg;base64,'.base64_encode((string) file_get_contents($ruta)) : null;
    }
}

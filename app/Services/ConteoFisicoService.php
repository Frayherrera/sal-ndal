<?php

namespace App\Services;

use App\Models\ConteoFisico;
use App\Models\DetalleConteoFisico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ConteoFisicoService
{
    public function generarCodigo(): string
    {
        $prefijo = 'CF';
        $base = $prefijo.'-'.strtoupper(Str::random(4));
        while (ConteoFisico::where('codigo', $base)->exists()) {
            $base = $prefijo.'-'.strtoupper(Str::random(4));
        }

        return $base;
    }

    public function crear(array $data): ConteoFisico
    {
        $data['codigo'] ??= $this->generarCodigo();
        $data['estado'] = 'borrador';
        $data['user_id'] = auth()->id();

        return ConteoFisico::create($data);
    }

    public function registrarDetalle(ConteoFisico $conteo, array $detalles): ConteoFisico
    {
        $this->verificarEditable($conteo);

        return DB::transaction(function () use ($conteo, $detalles) {
            $conteo->detalles()->delete();

            foreach ($detalles as $d) {
                DetalleConteoFisico::create([
                    'conteo_fisico_id' => $conteo->id,
                    'materia_prima_id' => $d['materia_prima_id'] ?? null,
                    'producto_terminado_id' => $d['producto_terminado_id'] ?? null,
                    'stock_sistema' => $d['stock_sistema'] ?? 0,
                    'cantidad_fisica' => $d['cantidad_fisica'] ?? 0,
                    'diferencia' => ($d['cantidad_fisica'] ?? 0) - ($d['stock_sistema'] ?? 0),
                    'motivo' => $d['motivo'] ?? null,
                ]);
            }

            return $conteo;
        });
    }

    public function completar(ConteoFisico $conteo): ConteoFisico
    {
        if ($conteo->estado !== 'borrador') {
            throw new RuntimeException('Solo un conteo en borrador puede completarse.');
        }

        if ($conteo->detalles()->count() === 0) {
            throw new RuntimeException('Debe registrar al menos un detalle antes de completar.');
        }

        $conteo->update([
            'estado' => 'completado',
            'fecha_completado' => now(),
        ]);

        return $conteo;
    }

    /**
     * Aprueba el conteo y genera los ajustes de inventario por diferencia.
     */
    public function aprobar(ConteoFisico $conteo): ConteoFisico
    {
        if ($conteo->estado !== 'completado') {
            throw new RuntimeException('Solo un conteo completado puede aprobarse.');
        }

        $movimientoService = app(MovimientoInventarioService::class);

        return DB::transaction(function () use ($conteo, $movimientoService) {
            foreach ($conteo->detalles as $detalle) {
                $diferencia = $detalle->diferencia;

                if ($diferencia === 0) {
                    continue;
                }

                $tipo = $diferencia > 0 ? 'ajuste_positivo' : 'ajuste_negativo';
                $cantidad = abs($diferencia);

                $origen = $detalle->materia_prima_id
                    ? $detalle->materiaPrima
                    : $detalle->productoTerminado;

                if (! $origen) {
                    continue;
                }

                $movimientoService->registrar([
                    'tipo' => $tipo,
                    'origen' => $origen,
                    'cantidad' => $cantidad,
                    'motivo' => 'Ajuste por conteo físico #'.$conteo->codigo.($detalle->motivo ? ': '.$detalle->motivo : ''),
                    'conteo_fisico_id' => $conteo->id,
                    'user_id' => auth()->id(),
                    'fecha' => now(),
                ]);
            }

            $conteo->update([
                'estado' => 'aprobado',
                'fecha_aprobado' => now(),
                'aprobado_por' => auth()->id(),
            ]);

            return $conteo;
        });
    }

    public function anular(ConteoFisico $conteo, ?string $motivo = null): ConteoFisico
    {
        if (in_array($conteo->estado, ['aprobado', 'anulado'], true)) {
            throw new RuntimeException('Un conteo aprobado o anulado no puede anularse.');
        }

        $conteo->update([
            'estado' => 'anulado',
            'fecha_anulado' => now(),
            'observaciones' => trim(($conteo->observaciones ?? '')."\nAnulado: ".($motivo ?? 'Sin motivo')),
        ]);

        return $conteo;
    }

    private function verificarEditable(ConteoFisico $conteo): void
    {
        if (! in_array($conteo->estado, ['borrador', 'completado'], true)) {
            throw new RuntimeException('No se puede modificar un conteo aprobado o anulado.');
        }
    }
}

<?php

namespace App\Services;

use App\Models\ConteoFisico;
use App\Models\DetalleConteoFisico;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
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

    /**
     * Reemplaza las líneas del conteo.
     *
     * Cada línea trae el artículo (`materia_prima_id` o `producto_terminado_id`
     * según el tipo del conteo), la `cantidad_fisica` en la unidad interna
     * (gramos para materia prima, unidades para producto) y un `motivo` opcional.
     *
     * El `stock_sistema` lo toma el servidor del inventario en este momento
     * (cualquier valor enviado se ignora), y la diferencia = físico − sistema.
     * Al aprobar se aplica esa diferencia, así que los movimientos posteriores
     * al conteo no se pierden.
     *
     * @param  array<int, array<string, mixed>>  $detalles
     */
    public function registrarDetalle(ConteoFisico $conteo, array $detalles): ConteoFisico
    {
        $this->verificarEditable($conteo);

        $esMateria = $conteo->tipo === 'materia_prima';
        $campo = $esMateria ? 'materia_prima_id' : 'producto_terminado_id';
        $modelo = $esMateria ? MateriaPrima::class : ProductoTerminado::class;

        $lineas = [];

        foreach ($detalles as $d) {
            $id = (int) ($d[$campo] ?? 0);

            if ($id <= 0) {
                throw new RuntimeException('Selecciona el '.($esMateria ? 'material' : 'producto').' en todas las líneas.');
            }

            if (isset($lineas[$id])) {
                throw new RuntimeException('Un mismo artículo aparece en dos líneas. Deja una sola línea por artículo.');
            }

            $cantidad = $d['cantidad_fisica'] ?? null;

            if ($cantidad === null || $cantidad === '' || ! is_numeric($cantidad) || (float) $cantidad < 0) {
                throw new RuntimeException('Indica la cantidad contada (0 o más) en todas las líneas.');
            }

            $motivo = trim((string) ($d['motivo'] ?? ''));

            $lineas[$id] = [
                'cantidad_fisica' => (int) round((float) $cantidad),
                'motivo' => $motivo !== '' ? $motivo : null,
            ];
        }

        if ($lineas === []) {
            throw new RuntimeException('Debes registrar al menos una línea.');
        }

        $articulos = $modelo::with('inventario')->whereIn('id', array_keys($lineas))->get()->keyBy('id');

        if ($articulos->count() !== count($lineas)) {
            throw new RuntimeException('Uno de los artículos seleccionados no existe.');
        }

        return DB::transaction(function () use ($conteo, $lineas, $articulos, $campo, $esMateria) {
            $conteo->detalles()->delete();

            foreach ($lineas as $id => $linea) {
                $articulo = $articulos->get($id);
                $stockSistema = $esMateria ? $articulo->stock_gramos() : $articulo->stock_disponible();

                DetalleConteoFisico::create([
                    'conteo_fisico_id' => $conteo->id,
                    $campo => $id,
                    'stock_sistema' => $stockSistema,
                    'cantidad_fisica' => $linea['cantidad_fisica'],
                    'diferencia' => $linea['cantidad_fisica'] - $stockSistema,
                    'motivo' => $linea['motivo'],
                ]);
            }

            return $conteo->load('detalles');
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

    /**
     * Solo un borrador admite cambios: al completarlo, las líneas quedan fijas
     * para revisión. Si hay que corregirlo, se anula y se hace uno nuevo.
     */
    private function verificarEditable(ConteoFisico $conteo): void
    {
        if ($conteo->estado !== 'borrador') {
            throw new RuntimeException('Solo se pueden modificar las líneas de un conteo en borrador.');
        }
    }
}

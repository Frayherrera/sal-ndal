<?php

namespace App\Services;

use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MateriaPrimaService
{
    public function __construct(
        protected MovimientoInventarioService $movimientoService,
        protected MolidoService $molidoService,
    ) {}

    public function generarCodigo(): string
    {
        $prefijo = 'MP';
        $base = $prefijo.'-'.strtoupper(Str::random(4));
        // Asegurar unicidad
        while (MateriaPrima::where('codigo', $base)->exists()) {
            $base = $prefijo.'-'.strtoupper(Str::random(4));
        }

        return $base;
    }

    /**
     * @param  array<int, array{ingrediente_id?: int, gramos_por_kg?: mixed}>  $lineas
     */
    public function crear(array $data, array $lineas = []): MateriaPrima
    {
        $data['codigo'] ??= $this->generarCodigo();
        $data['activo'] = $data['activo'] ?? true;
        $data['es_molido'] = $data['es_molido'] ?? false;

        return DB::transaction(function () use ($data, $lineas) {
            $mp = MateriaPrima::create($data);
            InventarioMateriaPrima::create([
                'materia_prima_id' => $mp->id,
                'stock_gramos' => 0,
                'costo_promedio' => 0,
            ]);

            if ($mp->es_molido) {
                $this->molidoService->guardarIngredientes($mp, $lineas);
            }

            return $mp;
        });
    }

    /**
     * @param  array<int, array{ingrediente_id?: int, gramos_por_kg?: mixed}>  $lineas
     */
    public function actualizar(MateriaPrima $mp, array $data, array $lineas = []): MateriaPrima
    {
        return DB::transaction(function () use ($mp, $data, $lineas) {
            $mp->update($data);

            if ($mp->es_molido) {
                $this->molidoService->guardarIngredientes($mp, $lineas);
            } else {
                $this->molidoService->eliminarIngredientes($mp);
            }

            return $mp;
        });
    }

    /**
     * Actualiza el stock de una materia prima creando un movimiento de ajuste.
     */
    public function actualizarStock(MateriaPrima $mp, int $nuevosGramos): MateriaPrima
    {
        $stockActual = $mp->stock_gramos();
        $diferencia = $nuevosGramos - $stockActual;

        if ($diferencia === 0) {
            return $mp;
        }

        $tipo = $diferencia > 0 ? 'ajuste_positivo' : 'ajuste_negativo';
        $cantidad = abs($diferencia);

        $this->movimientoService->registrar([
            'tipo' => $tipo,
            'origen' => $mp,
            'cantidad' => $cantidad,
            'motivo' => 'Ajuste manual desde edición de materia prima',
            'user_id' => auth()->id(),
        ]);

        return $mp->fresh(['inventario']);
    }

    public function eliminar(MateriaPrima $mp): void
    {
        if ($mp->movimientos()->exists()) {
            throw new RuntimeException('No se puede eliminar una materia prima con movimientos registrados. Desactívela en su lugar.');
        }

        $mp->delete();
    }

    public function toggleActivo(MateriaPrima $mp): MateriaPrima
    {
        $mp->update(['activo' => ! $mp->activo]);

        return $mp;
    }
}

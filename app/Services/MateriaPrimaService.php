<?php

namespace App\Services;

use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class MateriaPrimaService
{
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

    public function crear(array $data): MateriaPrima
    {
        $data['codigo'] ??= $this->generarCodigo();
        $data['activo'] = $data['activo'] ?? true;

        return DB::transaction(function () use ($data) {
            $mp = MateriaPrima::create($data);
            InventarioMateriaPrima::create([
                'materia_prima_id' => $mp->id,
                'stock_gramos' => 0,
                'costo_promedio' => 0,
            ]);

            return $mp;
        });
    }

    public function actualizar(MateriaPrima $mp, array $data): MateriaPrima
    {
        $mp->update($data);

        return $mp;
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

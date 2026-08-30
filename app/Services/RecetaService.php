<?php

namespace App\Services;

use App\Models\DetalleReceta;
use App\Models\ProductoTerminado;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RecetaService
{
    /**
     * Guarda (crea o reemplaza) las líneas de la receta de un producto terminado.
     *
     * @param  array<int, array{materia_prima_id?: int, gramos_por_unidad?: mixed}>  $lineas
     */
    public function guardar(ProductoTerminado $producto, array $lineas): ProductoTerminado
    {
        $limpia = $this->normalizarLineas($lineas);

        return DB::transaction(function () use ($producto, $limpia) {
            $producto->receta()->delete();

            foreach ($limpia as $linea) {
                DetalleReceta::create([
                    'producto_terminado_id' => $producto->id,
                    'materia_prima_id' => $linea['materia_prima_id'],
                    'gramos_por_unidad' => $linea['gramos_por_unidad'],
                ]);
            }

            $producto->load('receta');

            return $producto;
        });
    }

    public function eliminar(ProductoTerminado $producto): void
    {
        $producto->receta()->delete();
    }

    /**
     * Filtra líneas vacías, valida duplicados de materia prima y normaliza valores.
     *
     * @param  array<int, array{materia_prima_id?: int, gramos_por_unidad?: mixed}>  $lineas
     * @return array<int, array{materia_prima_id: int, gramos_por_unidad: float}>
     */
    private function normalizarLineas(array $lineas): array
    {
        $resultado = [];
        $vistos = [];

        foreach ($lineas as $linea) {
            $mpId = (int) ($linea['materia_prima_id'] ?? 0);
            $gramos = (float) ($linea['gramos_por_unidad'] ?? 0);

            if ($mpId <= 0) {
                continue;
            }

            if ($gramos <= 0) {
                throw new RuntimeException('El peso (gramos por unidad) debe ser mayor a cero.');
            }

            if (isset($vistos[$mpId])) {
                throw new RuntimeException('Una materia prima no puede repetirse dos veces en la misma receta.');
            }

            $vistos[$mpId] = true;
            $resultado[] = [
                'materia_prima_id' => $mpId,
                'gramos_por_unidad' => $gramos,
            ];
        }

        return $resultado;
    }
}

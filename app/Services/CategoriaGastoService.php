<?php

namespace App\Services;

use App\Models\CategoriaGasto;
use RuntimeException;

class CategoriaGastoService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function crear(array $data): CategoriaGasto
    {
        return CategoriaGasto::create([
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => (bool) ($data['activo'] ?? true),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function actualizar(CategoriaGasto $categoria, array $data): CategoriaGasto
    {
        $activo = (bool) ($data['activo'] ?? false);

        if ($categoria->esDelSistema() && ! $activo) {
            throw new RuntimeException('Esta categoría la usa el sistema para las compras automáticas y no se puede desactivar.');
        }

        $categoria->update([
            'nombre' => trim($data['nombre']),
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => $activo,
        ]);

        return $categoria->fresh();
    }

    /**
     * Elimina una categoría sin gastos. Si tiene gastos, se debe desactivar.
     */
    public function eliminar(CategoriaGasto $categoria): void
    {
        if ($categoria->esDelSistema()) {
            throw new RuntimeException('Esta categoría la usa el sistema y no se puede eliminar.');
        }

        if ($categoria->gastos()->exists()) {
            throw new RuntimeException("La categoría \"{$categoria->nombre}\" tiene gastos registrados. Desactívala en lugar de eliminarla.");
        }

        $categoria->delete();
    }
}

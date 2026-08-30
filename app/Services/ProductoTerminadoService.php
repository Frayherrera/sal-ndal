<?php

namespace App\Services;

use App\Models\InventarioProductoTerminado;
use App\Models\ProductoTerminado;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class ProductoTerminadoService
{
    public function generarCodigo(): string
    {
        $prefijo = 'PT';
        $base = $prefijo.'-'.strtoupper(Str::random(4));
        while (ProductoTerminado::where('codigo', $base)->exists()) {
            $base = $prefijo.'-'.strtoupper(Str::random(4));
        }

        return $base;
    }

    public function crear(array $data): ProductoTerminado
    {
        $data['codigo'] ??= $this->generarCodigo();
        $data['activo'] = $data['activo'] ?? true;

        return DB::transaction(function () use ($data) {
            $pt = ProductoTerminado::create($data);
            InventarioProductoTerminado::create([
                'producto_terminado_id' => $pt->id,
                'disponible' => 0,
                'comprometido' => 0,
            ]);

            return $pt;
        });
    }

    public function actualizar(ProductoTerminado $pt, array $data): ProductoTerminado
    {
        $pt->update($data);

        return $pt;
    }

    public function eliminar(ProductoTerminado $pt): void
    {
        if ($pt->movimientos()->exists()) {
            throw new RuntimeException('No se puede eliminar un producto terminado con movimientos registrados. Desactívelo en su lugar.');
        }

        $pt->delete();
    }

    public function toggleActivo(ProductoTerminado $pt): ProductoTerminado
    {
        $pt->update(['activo' => ! $pt->activo]);

        return $pt;
    }
}

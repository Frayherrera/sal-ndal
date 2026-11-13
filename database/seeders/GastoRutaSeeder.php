<?php

namespace Database\Seeders;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\User;
use App\Services\GastoService;
use Illuminate\Database\Seeder;

/**
 * Gastos de la ruta del 3 al 7 de noviembre de 2026, todos en la categoría "Ruta".
 *
 * Es idempotente: si un gasto (misma fecha, descripción y valor) ya existe en
 * la categoría, no se vuelve a crear.
 *
 *   php artisan db:seed --class=GastoRutaSeeder
 */
class GastoRutaSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: float}> fecha, descripción, valor */
    private const GASTOS = [
        ['2026-11-03', 'Comida', 70000],
        ['2026-11-04', 'Comida', 70000],
        ['2026-11-04', 'Lapicero', 1000],
        ['2026-11-05', 'A.C.P.M.', 129727],
        ['2026-11-05', 'Peaje', 16100],
        ['2026-11-05', 'Comida', 70000],
        ['2026-11-05', 'Hotel', 50000],
        ['2026-11-06', 'Hotel', 50000],
        ['2026-11-06', 'Comida', 70000],
        ['2026-11-07', 'Peaje', 16100],
        ['2026-11-07', 'Comida', 70000],
    ];

    public function run(GastoService $service): void
    {
        $categoria = CategoriaGasto::firstOrCreate(
            ['nombre' => 'Ruta'],
            ['descripcion' => 'Gastos de viaje en ruta: comida, hotel, combustible, peajes.', 'activo' => true]
        );

        // Si la categoría existía inactiva, se reactiva para poder registrar los gastos
        if (! $categoria->activo) {
            $categoria->update(['activo' => true]);
        }

        $usuario = User::where('email', env('ADMIN_EMAIL', 'admin@admin.co'))->first();

        $creados = 0;

        foreach (self::GASTOS as [$fecha, $descripcion, $monto]) {
            $existe = Gasto::where('categoria_gasto_id', $categoria->id)
                ->whereDate('fecha', $fecha)
                ->where('descripcion', $descripcion)
                ->where('monto', $monto)
                ->exists();

            if ($existe) {
                continue;
            }

            $service->crear([
                'fecha' => $fecha,
                'categoria_gasto_id' => $categoria->id,
                'descripcion' => $descripcion,
                'monto' => $monto,
                'metodo_pago' => null,
                'proveedor' => null,
                'user_id' => $usuario?->id,
            ]);

            $creados++;
        }

        $this->command?->info("Gastos de ruta creados: {$creados} de ".count(self::GASTOS).'.');
    }
}

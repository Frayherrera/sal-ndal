<?php

namespace Database\Seeders;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\User;
use App\Services\GastoService;
use Illuminate\Database\Seeder;

/**
 * Base para seeders que cargan una lista de gastos manuales en una categoría.
 *
 * Crea la categoría si no existe (o la reactiva) y es idempotente: un gasto con
 * la misma fecha, descripción y valor en esa categoría no se vuelve a crear.
 */
abstract class SeederGastosDeCategoria extends Seeder
{
    /** Nombre de la categoría donde quedan los gastos. */
    abstract protected function categoria(): string;

    /** Descripción de la categoría si hay que crearla. */
    protected function descripcionCategoria(): ?string
    {
        return null;
    }

    /**
     * @return array<int, array{0: string, 1: string, 2: float|int}> fecha (Y-m-d), descripción, valor
     */
    abstract protected function gastos(): array;

    public function run(GastoService $service): void
    {
        $categoria = CategoriaGasto::firstOrCreate(
            ['nombre' => $this->categoria()],
            ['descripcion' => $this->descripcionCategoria(), 'activo' => true]
        );

        // Si la categoría existía inactiva, se reactiva para poder registrar los gastos
        if (! $categoria->activo) {
            $categoria->update(['activo' => true]);
        }

        $usuario = User::where('email', env('ADMIN_EMAIL', 'admin@admin.co'))->first();
        $gastos = $this->gastos();
        $creados = 0;

        foreach ($gastos as [$fecha, $descripcion, $monto]) {
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

        $this->command?->info("Gastos \"{$categoria->nombre}\" creados: {$creados} de ".count($gastos).'.');
    }
}

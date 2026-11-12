<?php

namespace Database\Factories;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Gasto>
 */
class GastoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero' => null,
            'fecha' => now()->toDateString(),
            'categoria_gasto_id' => CategoriaGasto::factory(),
            'descripcion' => fake()->sentence(3),
            'monto' => 10000,
            'metodo_pago' => 'efectivo',
            'proveedor' => null,
            'estado' => Gasto::ESTADO_REGISTRADO,
            'movimiento_inventario_id' => null,
            'user_id' => User::factory(),
        ];
    }
}

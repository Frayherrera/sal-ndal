<?php

namespace Database\Factories;

use App\Models\MateriaPrima;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MateriaPrima>
 */
class MateriaPrimaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => 'MP-'.strtoupper($this->faker->unique()->lexify('????')),
            'nombre' => $this->faker->randomElement(['Orégano', 'Comino', 'Pimentón', 'Curry', 'Cúrcuma', 'Achiote', 'Canela', 'Clavo']),
            'categoria' => $this->faker->randomElement(['Hierbas', 'Semillas', 'Colorantes']),
            'unidad_base' => 'kg',
            'stock_minimo' => 5,
            'proveedor' => $this->faker->company(),
            'ubicacion' => $this->faker->randomElement(['Bodega A', 'Bodega B', 'Estante 1']),
            'activo' => true,
        ];
    }
}

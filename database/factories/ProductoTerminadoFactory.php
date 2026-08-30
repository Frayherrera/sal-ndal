<?php

namespace Database\Factories;

use App\Models\ProductoTerminado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProductoTerminado>
 */
class ProductoTerminadoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => 'PT-'.strtoupper($this->faker->unique()->lexify('????')),
            'nombre' => $this->faker->randomElement(['Comino 100g', 'Orégano 30g', 'Pimentón 50g', 'Curry 30g', 'Cúrcuma 100g']),
            'categoria' => $this->faker->randomElement(['Hierbas', 'Condimentos']),
            'presentacion' => $this->faker->randomElement(['bolsa', 'frasco']),
            'peso_neto' => $this->faker->randomElement([30, 50, 100]),
            'precio_venta' => $this->faker->randomFloat(2, 3000, 20000),
            'stock_minimo' => 20,
            'activo' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\CategoriaGasto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CategoriaGasto>
 */
class CategoriaGastoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Categoría '.fake()->unique()->numerify('####'),
            'descripcion' => null,
            'clave' => null,
            'activo' => true,
        ];
    }
}

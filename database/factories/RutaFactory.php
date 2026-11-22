<?php

namespace Database\Factories;

use App\Models\Ruta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ruta>
 */
class RutaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => 'Ruta '.fake()->unique()->numerify('###'),
            'orden' => 1,
            'ciudades' => [],
            'descripcion' => null,
            'activo' => true,
        ];
    }
}

<?php

namespace Database\Factories;

use App\Models\ConteoFisico;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ConteoFisico>
 */
class ConteoFisicoFactory extends Factory
{
    public function definition(): array
    {
        return [
            'codigo' => 'CF-'.strtoupper($this->faker->unique()->lexify('????')),
            'tipo' => $this->faker->randomElement(['materia_prima', 'producto_terminado']),
            'estado' => 'borrador',
            'fecha_conteo' => $this->faker->date(),
        ];
    }
}

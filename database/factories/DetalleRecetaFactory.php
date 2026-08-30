<?php

namespace Database\Factories;

use App\Models\DetalleReceta;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleReceta>
 */
class DetalleRecetaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'producto_terminado_id' => ProductoTerminado::factory(),
            'materia_prima_id' => MateriaPrima::factory(),
            'gramos_por_unidad' => $this->faker->randomFloat(3, 0.500, 150),
        ];
    }
}

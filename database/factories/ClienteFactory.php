<?php

namespace Database\Factories;

use App\Models\Cliente;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Cliente>
 */
class ClienteFactory extends Factory
{
    public function definition(): array
    {
        return [
            'nombre' => $this->faker->name(),
            'local' => $this->faker->randomElement(['Tienda Central', 'Supermercado La 80', 'Restaurante El Fogón', 'Distribuidora Norte', 'Tienda Doña Rosa']),
            'ciudad' => $this->faker->randomElement(['Bogotá', 'Medellín', 'Cali', 'Barranquilla', 'Pereira']),
            'telefono' => $this->faker->numerify('3## ### ####'),
        ];
    }
}

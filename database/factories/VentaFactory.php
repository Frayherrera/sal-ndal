<?php

namespace Database\Factories;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Venta>
 */
class VentaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'numero' => null,
            'cliente_id' => Cliente::factory(),
            'user_id' => User::factory(),
            'fecha' => now(),
            'subtotal' => 0,
            'descuento_total' => 0,
            'total' => 0,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'fecha_vencimiento' => null,
            'observaciones' => null,
        ];
    }
}

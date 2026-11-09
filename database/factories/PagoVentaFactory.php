<?php

namespace Database\Factories;

use App\Models\PagoVenta;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PagoVenta>
 */
class PagoVentaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venta_id' => Venta::factory(),
            'fecha' => now(),
            'monto' => 10000,
            'metodo' => 'efectivo',
            'referencia' => null,
            'nota' => null,
            'user_id' => User::factory(),
        ];
    }
}

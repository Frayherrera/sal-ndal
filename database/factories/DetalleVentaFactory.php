<?php

namespace Database\Factories;

use App\Models\DetalleVenta;
use App\Models\ProductoTerminado;
use App\Models\Venta;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<DetalleVenta>
 */
class DetalleVentaFactory extends Factory
{
    public function definition(): array
    {
        return [
            'venta_id' => Venta::factory(),
            'producto_terminado_id' => ProductoTerminado::factory(),
            'cantidad_unidades' => 1,
            'precio_unidad' => 1200,
            'descuento_tipo' => 'monto',
            'descuento_valor' => 0,
            'descuento_monto' => 0,
            'subtotal' => 1200,
            'total' => 1200,
        ];
    }
}

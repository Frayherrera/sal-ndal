<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\Venta;
use Database\Seeders\ClienteSeeder;
use Database\Seeders\VentaNoviembreSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VentaNoviembreSeederTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function crea_las_ventas_de_contado_de_clientes_existentes_sin_duplicar(): void
    {
        $this->seed(ClienteSeeder::class);

        $this->seed(VentaNoviembreSeeder::class);
        $this->seed(VentaNoviembreSeeder::class); // segunda corrida: no debe duplicar

        $ventas = Venta::withSum('pagos', 'monto')->get();

        // 42 filas − 4 de clientes que no existen (OVELMAR ANTONIO, IVAN CARDENAS, PROVEDOR PAISA ×2)
        $this->assertCount(38, $ventas);
        $this->assertSame(5086836.0, round((float) $ventas->sum('total'), 2));
        $this->assertTrue($ventas->every(fn (Venta $v) => $v->forma_pago === Venta::FORMA_CONTADO
            && $v->estadoPago() === 'pagada'
            && $v->numero !== null));

        // Nombres con espacios extra se emparejan igual
        $luz = Cliente::where('nombre', 'LUZ MARINA LEON')->firstOrFail();
        $this->assertSame(1, Venta::where('cliente_id', $luz->id)->count());

        // Un mismo cliente puede tener dos ventas el mismo día con totales distintos
        $ramon = Cliente::where('nombre', 'RAMON PACHECO')->firstOrFail();
        $this->assertSame(2, Venta::where('cliente_id', $ramon->id)->count());

        $this->assertDatabaseMissing('clientes', ['nombre' => 'PROVEDOR PAISA']);
    }
}

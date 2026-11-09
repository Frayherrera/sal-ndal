<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ProductoTerminado;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReporteVentaFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function venta(Cliente $cliente, array $atributos = []): Venta
    {
        return Venta::factory()->create(array_merge([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'fecha' => now(),
            'subtotal' => 48000,
            'total' => 48000,
        ], $atributos));
    }

    private function detalle(Venta $venta, ProductoTerminado $producto, int $unidades = 2, float $precio = 1200): void
    {
        $bruto = $unidades * $precio;

        $venta->detalles()->create([
            'producto_terminado_id' => $producto->id,
            'cantidad_unidades' => $unidades,
            'precio_unidad' => $precio,
            'descuento_tipo' => 'monto',
            'descuento_valor' => 0,
            'descuento_monto' => 0,
            'subtotal' => $bruto,
            'total' => $bruto,
        ]);
    }

    #[Test]
    public function los_reportes_requieren_autenticacion(): void
    {
        $this->get('/ventas/reportes/clientes')->assertRedirect(route('login'));
        $this->get('/ventas/reportes/productos')->assertRedirect(route('login'));
    }

    #[Test]
    public function reporte_de_ventas_por_cliente_agrega_los_totales(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Distribuidora Reporte']);

        $this->venta($cliente, ['total' => 48000]);
        $this->venta($cliente, ['total' => 24000]);

        $this->actingAs($this->user)
            ->get('/ventas/reportes/clientes')
            ->assertOk()
            ->assertSee('Distribuidora Reporte')
            ->assertSee('$72.000')
            ->assertSee('2');
    }

    #[Test]
    public function reporte_de_ventas_por_cliente_calcula_abonos_y_saldo(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Cliente Con Abono']);
        $venta = $this->venta($cliente, ['total' => 50000]);
        $venta->pagos()->create([
            'fecha' => now(),
            'monto' => 20000,
            'metodo' => 'efectivo',
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get('/ventas/reportes/clientes')
            ->assertOk()
            ->assertSee('Cliente Con Abono')
            ->assertSee('$20.000')  // abonado
            ->assertSee('$30.000'); // saldo
    }

    #[Test]
    public function reporte_por_cliente_respeta_el_filtro_de_fechas(): void
    {
        $reciente = Cliente::factory()->create(['nombre' => 'Cliente Reciente']);
        $antiguo = Cliente::factory()->create(['nombre' => 'Cliente Antiguo']);

        $this->venta($reciente, ['fecha' => now()]);
        $this->venta($antiguo, ['fecha' => now()->subDays(10)]);

        $this->actingAs($this->user)
            ->get('/ventas/reportes/clientes?desde='.now()->subDay()->toDateString())
            ->assertOk()
            ->assertSee('Cliente Reciente')
            ->assertDontSee('Cliente Antiguo');
    }

    #[Test]
    public function reporte_de_productos_mas_vendidos_agrega_unidades_e_ingresos(): void
    {
        $cliente = Cliente::factory()->create();
        $producto = ProductoTerminado::factory()->create(['nombre' => 'Comino Especial']);

        $venta1 = $this->venta($cliente);
        $venta2 = $this->venta($cliente);

        $this->detalle($venta1, $producto, 2, 1200);
        $this->detalle($venta2, $producto, 1, 1200);

        $this->actingAs($this->user)
            ->get('/ventas/reportes/productos')
            ->assertOk()
            ->assertSee('Comino Especial')
            ->assertSee('3')       // unidades
            ->assertSee('$3.600');
    }

    #[Test]
    public function reporte_de_productos_ignora_ventas_anuladas(): void
    {
        $cliente = Cliente::factory()->create();
        $producto = ProductoTerminado::factory()->create(['nombre' => 'Producto Anulado']);

        $venta = $this->venta($cliente, ['estado' => Venta::ESTADO_ANULADA]);
        $this->detalle($venta, $producto, 3, 1200);

        $this->actingAs($this->user)
            ->get('/ventas/reportes/productos')
            ->assertOk()
            ->assertDontSee('Producto Anulado');
    }

    #[Test]
    public function reporte_de_productos_respeta_el_filtro_de_fechas(): void
    {
        $cliente = Cliente::factory()->create();
        $producto = ProductoTerminado::factory()->create(['nombre' => 'Producto Viejo']);

        $venta = $this->venta($cliente, ['fecha' => now()->subDays(10)]);
        $this->detalle($venta, $producto, 1, 1200);

        $this->actingAs($this->user)
            ->get('/ventas/reportes/productos?desde='.now()->subDay()->toDateString())
            ->assertOk()
            ->assertDontSee('Producto Viejo');
    }
}

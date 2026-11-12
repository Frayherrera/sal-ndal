<?php

namespace Tests\Feature;

use App\Models\Cliente;
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

    #[Test]
    public function los_reportes_requieren_autenticacion(): void
    {
        $this->get('/ventas/reportes/clientes')->assertRedirect(route('login'));
    }

    #[Test]
    public function el_reporte_de_productos_ya_no_existe(): void
    {
        $this->actingAs($this->user)
            ->get('/ventas/reportes/productos')
            ->assertNotFound();
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
}

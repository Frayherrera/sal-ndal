<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\InventarioProductoTerminado;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class VentaFeatureTest extends TestCase
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
        $venta = Venta::factory()->create(array_merge([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'subtotal' => 24000,
            'total' => 24000,
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ], $atributos));

        $venta->numero = 'VTA-'.str_pad((string) $venta->id, 5, '0', STR_PAD_LEFT);
        $venta->save();

        return $venta;
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    private function datosVenta(Cliente $cliente, array $extra = []): array
    {
        return array_merge([
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'forma_pago' => 'credito',
            'abono_inicial' => 0,
            'metodo_pago' => 'efectivo',
            'total' => 85000,
        ], $extra);
    }

    #[Test]
    public function las_rutas_de_ventas_requieren_autenticacion(): void
    {
        $this->get('/ventas')->assertRedirect(route('login'));
        $this->get('/ventas/crear')->assertRedirect(route('login'));
        $this->get('/ventas/cartera')->assertRedirect(route('login'));
    }

    #[Test]
    public function lista_las_ventas_con_su_estado(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Tienda La Esquina']);
        $venta = $this->venta($cliente);

        $this->actingAs($this->user)
            ->get('/ventas')
            ->assertOk()
            ->assertSee($venta->numero)
            ->assertSee('Tienda La Esquina')
            ->assertSee('Pendiente');
    }

    #[Test]
    public function el_formulario_de_creacion_pide_el_valor_total_y_no_productos(): void
    {
        Cliente::factory()->create(['nombre' => 'Cliente Formulario']);
        $pt = ProductoTerminado::factory()->create(['nombre' => 'Paprika Especial 50g', 'activo' => true]);

        $this->actingAs($this->user)
            ->get('/ventas/crear')
            ->assertOk()
            ->assertSee('Cliente Formulario')
            ->assertSee('Nueva Venta')
            ->assertSee('name="total"', false)
            ->assertDontSee($pt->nombre)
            ->assertDontSee('lineas[', false);
    }

    #[Test]
    public function registra_una_venta_a_credito_desde_el_formulario(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($this->user)->post('/ventas', $this->datosVenta($cliente, [
            'abono_inicial' => 1000,
            'observaciones' => 'Surtido de especias',
        ]));

        $venta = Venta::first();

        $this->assertNotNull($venta);
        $response->assertRedirect(route('ventas.show', $venta));
        $this->assertDatabaseHas('ventas', [
            'id' => $venta->id,
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'forma_pago' => 'credito',
            'observaciones' => 'Surtido de especias',
        ]);
        $this->assertSame(85000.0, (float) $venta->total);
        $this->assertSame(84000.0, $venta->saldo());
        $this->assertDatabaseHas('pago_venta', [
            'venta_id' => $venta->id,
            'monto' => 1000,
        ]);
        $this->assertSame(0, MovimientoInventario::count());
    }

    #[Test]
    public function registra_una_venta_de_contado_pagando_el_total_sin_vencimiento(): void
    {
        $cliente = Cliente::factory()->create();

        $response = $this->actingAs($this->user)->post('/ventas', $this->datosVenta($cliente, [
            'fecha_vencimiento' => '',
            'forma_pago' => 'contado',
            'abono_inicial' => '',
            'total' => 2400,
        ]));

        $venta = Venta::firstOrFail();

        $response->assertRedirect(route('ventas.show', $venta));
        $this->assertSame('contado', $venta->forma_pago);
        $this->assertNull($venta->fecha_vencimiento);
        $this->assertSame(2400.0, (float) $venta->total);
        $this->assertSame(0.0, $venta->saldo());
        $this->assertSame('pagada', $venta->estadoPago());
        $this->assertDatabaseHas('pago_venta', [
            'venta_id' => $venta->id,
            'monto' => 2400,
        ]);
    }

    #[Test]
    public function no_descuenta_stock_de_productos(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 5,
            'comprometido' => 0,
        ]);

        $this->actingAs($this->user)->post('/ventas', $this->datosVenta($cliente));

        $this->assertDatabaseCount('ventas', 1);
        $this->assertSame(5, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function valida_cliente_y_total(): void
    {
        $this->actingAs($this->user)
            ->post('/ventas', [
                'fecha' => now()->toDateString(),
                'forma_pago' => 'contado',
            ])
            ->assertSessionHasErrors(['cliente_id', 'total']);

        $this->assertDatabaseCount('ventas', 0);
    }

    #[Test]
    public function rechaza_total_cero(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->user)
            ->post('/ventas', $this->datosVenta($cliente, ['total' => 0]))
            ->assertSessionHasErrors('total');

        $this->assertDatabaseCount('ventas', 0);
    }

    #[Test]
    public function rechaza_fechas_futuras(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->user)
            ->post('/ventas', $this->datosVenta($cliente, [
                'fecha' => now()->addDay()->toDateString(),
                'fecha_vencimiento' => now()->addDays(31)->toDateString(),
            ]))
            ->assertSessionHasErrors('fecha');
    }

    #[Test]
    public function devuelve_el_error_del_servicio_al_formulario(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->user)
            ->from('/ventas/crear')
            ->post('/ventas', $this->datosVenta($cliente, [
                'total' => 1000,
                'abono_inicial' => 5000,
            ]))
            ->assertRedirect('/ventas/crear')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ventas', 0);
    }

    #[Test]
    public function muestra_el_detalle_de_la_venta(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Detalle Cliente']);
        $venta = $this->venta($cliente, ['observaciones' => 'Pedido de prueba']);

        $this->actingAs($this->user)
            ->get("/ventas/{$venta->id}")
            ->assertOk()
            ->assertSee($venta->numero)
            ->assertSee('Detalle Cliente')
            ->assertSee('Venta por valor total')
            ->assertSee('Pedido de prueba')
            ->assertSee('Abonos');
    }

    #[Test]
    public function muestra_el_recibo_imprimible(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Recibo Cliente']);
        $venta = $this->venta($cliente, ['observaciones' => 'Concepto del recibo']);

        $this->actingAs($this->user)
            ->get("/ventas/{$venta->id}/recibo")
            ->assertOk()
            ->assertSee($venta->numero)
            ->assertSee('Recibo Cliente')
            ->assertSee('Concepto del recibo')
            ->assertSee('window.print()', false);
    }

    #[Test]
    public function anula_una_venta(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->user)->post('/ventas', $this->datosVenta($cliente));

        $venta = Venta::firstOrFail();

        $this->actingAs($this->user)
            ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Cliente desistió'])
            ->assertRedirect(route('ventas.show', $venta));

        $this->assertSame(Venta::ESTADO_ANULADA, $venta->fresh()->estado);
    }

    #[Test]
    public function no_anula_una_venta_con_abonos(): void
    {
        $cliente = Cliente::factory()->create();
        $venta = $this->venta($cliente);
        $venta->pagos()->create([
            'fecha' => now(),
            'monto' => 5000,
            'metodo' => 'efectivo',
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->from("/ventas/{$venta->id}/anular")
            ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Intento'])
            ->assertRedirect("/ventas/{$venta->id}/anular")
            ->assertSessionHas('error');

        $this->assertSame(Venta::ESTADO_CONFIRMADA, $venta->fresh()->estado);
    }

    #[Test]
    public function la_vista_de_anulacion_bloquea_ventas_con_abonos(): void
    {
        $cliente = Cliente::factory()->create();
        $venta = $this->venta($cliente);
        $venta->pagos()->create([
            'fecha' => now(),
            'monto' => 5000,
            'metodo' => 'efectivo',
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get("/ventas/{$venta->id}/anular")
            ->assertOk()
            ->assertSee('No se puede anular');
    }

    #[Test]
    public function exporta_las_ventas_en_csv(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Cliente CSV']);
        $venta = $this->venta($cliente);

        $response = $this->actingAs($this->user)->get('/ventas/exportar');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Número;Fecha;Cliente', $response->streamedContent());
        $this->assertStringContainsString($venta->numero, $response->streamedContent());
        $this->assertStringContainsString('Cliente CSV', $response->streamedContent());
    }
}

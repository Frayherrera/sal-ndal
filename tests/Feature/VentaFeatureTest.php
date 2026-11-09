<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\InventarioProductoTerminado;
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

    private function producto(int $stock = 100, float $precio = 24000): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create([
            'precio_venta' => $precio,
            'activo' => true,
        ]);

        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => $stock,
            'comprometido' => 0,
        ]);

        return $pt;
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
    public function el_formulario_de_creacion_se_renderiza(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Cliente Formulario']);
        $pt = $this->producto(50);

        $this->actingAs($this->user)
            ->get('/ventas/crear')
            ->assertOk()
            ->assertSee('Cliente Formulario')
            ->assertSee($pt->nombre)
            ->assertSee('Nueva Venta');
    }

    #[Test]
    public function registra_una_venta_desde_el_formulario(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(100);

        $response = $this->actingAs($this->user)->post('/ventas', [
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'forma_pago' => 'credito',
            'abono_inicial' => 1000,
            'metodo_pago' => 'efectivo',
            'lineas' => [[
                'producto_terminado_id' => $pt->id,
                'cantidad_unidades' => 2,
                'descuento_tipo' => 'porcentaje',
                'descuento_valor' => 5,
            ]],
        ]);

        $venta = Venta::first();

        $this->assertNotNull($venta);
        $response->assertRedirect(route('ventas.show', $venta));
        $this->assertDatabaseHas('ventas', [
            'id' => $venta->id,
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'estado' => Venta::ESTADO_CONFIRMADA,
        ]);
        $this->assertSame(2280.0, (float) $venta->total); // 2.400 − 5%
        $this->assertSame(98, $pt->fresh()->inventario->disponible);
        $this->assertDatabaseHas('pago_venta', [
            'venta_id' => $venta->id,
            'monto' => 1000,
        ]);
    }

    #[Test]
    public function registra_una_venta_de_contado_pagando_el_total_sin_vencimiento(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(100);

        $response = $this->actingAs($this->user)->post('/ventas', [
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => '',
            'forma_pago' => 'contado',
            'abono_inicial' => '',
            'metodo_pago' => 'efectivo',
            'lineas' => [[
                'producto_terminado_id' => $pt->id,
                'cantidad_unidades' => 2,
            ]],
        ]);

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
    public function valida_cliente_y_lineas(): void
    {
        $this->actingAs($this->user)
            ->post('/ventas', [
                'fecha' => now()->toDateString(),
                'lineas' => [],
            ])
            ->assertSessionHasErrors(['cliente_id', 'lineas']);

        $this->assertDatabaseCount('ventas', 0);
    }

    #[Test]
    public function rechaza_fechas_futuras(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto();

        $this->actingAs($this->user)
            ->post('/ventas', [
                'cliente_id' => $cliente->id,
                'fecha' => now()->addDay()->toDateString(),
                'lineas' => [[
                    'producto_terminado_id' => $pt->id,
                    'cantidad_unidades' => 1,
                ]],
            ])
            ->assertSessionHasErrors('fecha');
    }

    #[Test]
    public function devuelve_el_error_de_stock_al_formulario(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(5);

        $this->actingAs($this->user)
            ->from('/ventas/crear')
            ->post('/ventas', [
                'cliente_id' => $cliente->id,
                'fecha' => now()->toDateString(),
                'fecha_vencimiento' => now()->addDays(30)->toDateString(),
                'forma_pago' => 'credito',
                'lineas' => [[
                    'producto_terminado_id' => $pt->id,
                    'cantidad_unidades' => 10,
                ]],
            ])
            ->assertRedirect('/ventas/crear')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('ventas', 0);
    }

    #[Test]
    public function muestra_el_detalle_de_la_venta(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Detalle Cliente']);
        $venta = $this->venta($cliente);

        $this->actingAs($this->user)
            ->get("/ventas/{$venta->id}")
            ->assertOk()
            ->assertSee($venta->numero)
            ->assertSee('Detalle Cliente')
            ->assertSee('Abonos');
    }

    #[Test]
    public function muestra_el_recibo_imprimible(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Recibo Cliente']);
        $venta = $this->venta($cliente);

        $this->actingAs($this->user)
            ->get("/ventas/{$venta->id}/recibo")
            ->assertOk()
            ->assertSee($venta->numero)
            ->assertSee('Recibo Cliente')
            ->assertSee('window.print()', false);
    }

    #[Test]
    public function anula_una_venta_y_revierte_el_stock(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(100);

        $this->actingAs($this->user)->post('/ventas', [
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'forma_pago' => 'credito',
            'lineas' => [[
                'producto_terminado_id' => $pt->id,
                'cantidad_unidades' => 1,
            ]],
        ]);

        $venta = Venta::firstOrFail();

        $this->actingAs($this->user)
            ->post("/ventas/{$venta->id}/anular", ['motivo' => 'Cliente desistió'])
            ->assertRedirect(route('ventas.show', $venta));

        $this->assertSame(Venta::ESTADO_ANULADA, $venta->fresh()->estado);
        $this->assertSame(100, $pt->fresh()->inventario->disponible);
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

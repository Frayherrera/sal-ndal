<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\ConteoFisico;
use App\Models\InventarioMateriaPrima;
use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ViewsSmokeTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function dashboard_se_renders(): void
    {
        $this->actingAs($this->user)->get('/gestion')->assertOk();
    }

    #[Test]
    public function vistas_de_materia_prima_se_renders(): void
    {
        $mp = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => 1000, 'costo_promedio' => 5000]);
        $mp->movimientos()->create([
            'tipo' => 'compra_recepcion',
            'origen_type' => $mp->getMorphClass(),
            'origen_id' => $mp->id,
            'cantidad' => 1000,
            'direccion' => 'entrada',
            'saldo' => 1000,
            'fecha' => now(),
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)->get('/inventario/materia-prima')->assertOk();
        $this->actingAs($this->user)->get('/inventario/materia-prima/create')->assertOk();
        $this->actingAs($this->user)->get("/inventario/materia-prima/{$mp->id}")->assertOk();
        $this->actingAs($this->user)->get("/inventario/materia-prima/{$mp->id}/edit")->assertOk();
    }

    #[Test]
    public function vistas_de_productos_terminados_se_renders(): void
    {
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create(['producto_terminado_id' => $pt->id, 'disponible' => 50, 'comprometido' => 0]);

        $this->actingAs($this->user)->get('/inventario/productos-terminados')->assertOk();
        $this->actingAs($this->user)->get('/inventario/productos-terminados/create')->assertOk();
        $this->actingAs($this->user)->get("/inventario/productos-terminados/{$pt->id}")->assertOk();
        $this->actingAs($this->user)->get("/inventario/productos-terminados/{$pt->id}/edit")->assertOk();
    }

    #[Test]
    public function vistas_de_movimientos_se_renders(): void
    {
        $pt = ProductoTerminado::factory()->create();
        $mov = $pt->movimientos()->create([
            'tipo' => 'venta_despacho',
            'origen_type' => $pt->getMorphClass(),
            'origen_id' => $pt->id,
            'cantidad' => 10,
            'direccion' => 'salida',
            'saldo' => 40,
            'fecha' => now(),
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)->get('/inventario/movimientos')->assertOk();
        $this->actingAs($this->user)->get('/inventario/movimientos/crear')->assertOk();
        $this->actingAs($this->user)->get("/inventario/movimientos/{$mov->id}")->assertOk();
        $this->actingAs($this->user)->get("/inventario/movimientos/{$mov->id}/anular")->assertOk();
    }

    #[Test]
    public function vistas_de_conteo_fisico_se_renders(): void
    {
        $mp = MateriaPrima::factory()->create();
        $conteo = ConteoFisico::factory()->create(['tipo' => 'materia_prima', 'user_id' => $this->user->id]);
        $conteo->detalles()->create([
            'materia_prima_id' => $mp->id,
            'stock_sistema' => 1000,
            'cantidad_fisica' => 950,
            'diferencia' => -50,
        ]);

        $this->actingAs($this->user)->get('/inventario/conteo-fisico')->assertOk();
        $this->actingAs($this->user)->get('/inventario/conteo-fisico/crear?tipo=materia_prima')->assertOk();
        $this->actingAs($this->user)->get("/inventario/conteo-fisico/{$conteo->id}")->assertOk();
    }

    #[Test]
    public function vistas_de_clientes_se_renders(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->user)->get('/clientes')->assertOk();
        $this->actingAs($this->user)->get('/clientes/create')->assertOk();
        $this->actingAs($this->user)->get("/clientes/{$cliente->id}/edit")->assertOk();
    }

    #[Test]
    public function vistas_de_ventas_se_renders(): void
    {
        $cliente = Cliente::factory()->create();
        $producto = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $producto->id,
            'disponible' => 100,
            'comprometido' => 0,
        ]);

        $venta = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'subtotal' => 1200,
            'total' => 1200,
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ]);

        $venta->forceFill(['numero' => 'VTA-00001'])->save();

        $venta->detalles()->create([
            'producto_terminado_id' => $producto->id,
            'cantidad_unidades' => 1,
            'precio_unidad' => 1200,
            'descuento_tipo' => 'monto',
            'descuento_valor' => 0,
            'descuento_monto' => 0,
            'subtotal' => 1200,
            'total' => 1200,
        ]);

        $this->actingAs($this->user)->get('/ventas')->assertOk();
        $this->actingAs($this->user)->get('/ventas/crear')->assertOk();
        $this->actingAs($this->user)->get("/ventas/{$venta->id}")->assertOk();
        $this->actingAs($this->user)->get("/ventas/{$venta->id}/recibo")->assertOk();
        $this->actingAs($this->user)->get("/ventas/{$venta->id}/anular")->assertOk();
        $this->actingAs($this->user)->get("/ventas/{$venta->id}/abonos/crear")->assertOk();
        $this->actingAs($this->user)->get('/ventas/cartera')->assertOk();
        $this->actingAs($this->user)->get('/ventas/reportes/clientes')->assertOk();

        // Venta nueva, por valor total (sin detalle)
        $ventaTotal = Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'subtotal' => 50000,
            'total' => 50000,
            'observaciones' => 'Venta por total',
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ]);
        $ventaTotal->forceFill(['numero' => 'VTA-00002'])->save();

        $this->actingAs($this->user)->get("/ventas/{$ventaTotal->id}")->assertOk()->assertSee('Venta por total');
        $this->actingAs($this->user)->get("/ventas/{$ventaTotal->id}/recibo")->assertOk()->assertSee('Venta por total');
    }

    #[Test]
    public function alertas_se_renders(): void
    {
        $this->actingAs($this->user)->get('/inventario/alertas')->assertOk();
    }

    #[Test]
    public function vistas_de_recetas_y_produccion_se_renders(): void
    {
        $mp = MateriaPrima::factory()->create();
        $pt = ProductoTerminado::factory()->create();

        $this->actingAs($this->user)->get('/inventario/recetas')->assertOk();
        $this->actingAs($this->user)->get('/inventario/recetas/crear')->assertOk();
        $this->actingAs($this->user)->get("/inventario/recetas/{$pt->id}/editar")->assertOk();

        // Producto con receta para la vista de producción con desglose
        $pt->receta()->create(['materia_prima_id' => $mp->id, 'gramos_por_unidad' => 10]);
        $this->actingAs($this->user)->get('/inventario/produccion/crear')->assertOk();

        $this->actingAs($this->user)
            ->get("/inventario/productos-terminados/{$pt->id}")
            ->assertOk()
            ->assertSee('Receta');
    }
}

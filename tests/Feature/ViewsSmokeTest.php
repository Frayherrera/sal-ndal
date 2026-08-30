<?php

namespace Tests\Feature;

use App\Models\ConteoFisico;
use App\Models\InventarioMateriaPrima;
use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Models\User;
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

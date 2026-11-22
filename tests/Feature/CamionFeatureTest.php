<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\InventarioProductoTerminado;
use App\Models\ProductoTerminado;
use App\Models\Ruta;
use App\Models\User;
use App\Models\ViajeCamion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CamionFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function producto(string $nombre, int $paquetes = 50): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create(['nombre' => $nombre, 'precio_venta' => 24000, 'activo' => true]);
        InventarioProductoTerminado::create(['producto_terminado_id' => $pt->id, 'disponible' => $paquetes * 20, 'comprometido' => 0]);

        return $pt;
    }

    #[Test]
    public function las_rutas_del_camion_requieren_autenticacion(): void
    {
        $this->get('/camion')->assertRedirect(route('login'));
        $this->get('/camion/cargar')->assertRedirect(route('login'));
        $this->get('/camion/rutas')->assertRedirect(route('login'));
        $this->get('/camion/reportes/semanal')->assertRedirect(route('login'));
    }

    #[Test]
    public function sin_rutas_pide_crearlas_primero(): void
    {
        $this->actingAs($this->user)->get('/camion')->assertOk()->assertSee('Crear la primera ruta');
        $this->actingAs($this->user)->get('/camion/cargar')->assertRedirect(route('camion.rutas.create'));
    }

    #[Test]
    public function crud_de_rutas_con_ciudades_de_los_clientes(): void
    {
        Cliente::factory()->create(['ciudad' => 'PELAYA']);
        Cliente::factory()->create(['ciudad' => 'LA GLORIA']);

        $this->actingAs($this->user)->get('/camion/rutas/create')->assertOk()->assertSee('PELAYA')->assertSee('LA GLORIA');

        $this->actingAs($this->user)->post('/camion/rutas', [
            'nombre' => 'Pelaya – La Gloria', 'orden' => 1, 'ciudades' => ['PELAYA', 'LA GLORIA'], 'activo' => '1',
        ])->assertRedirect(route('camion.rutas.index'));

        $ruta = Ruta::firstOrFail();
        $this->assertSame(['PELAYA', 'LA GLORIA'], $ruta->ciudades);
        $this->assertTrue($ruta->activo);

        $this->actingAs($this->user)->post('/camion/rutas', ['nombre' => 'Pelaya – La Gloria', 'orden' => 2])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($this->user)->get("/camion/rutas/{$ruta->id}/edit")->assertOk();
        $this->actingAs($this->user)->put("/camion/rutas/{$ruta->id}", ['nombre' => 'Ruta norte', 'orden' => 3, 'activo' => '0'])
            ->assertRedirect(route('camion.rutas.index'));

        $this->assertSame('Ruta norte', $ruta->fresh()->nombre);
        $this->assertFalse($ruta->fresh()->activo);
        $this->assertSame([], $ruta->fresh()->ciudades);

        $this->actingAs($this->user)->delete("/camion/rutas/{$ruta->id}")->assertRedirect(route('camion.rutas.index'));
        $this->assertDatabaseCount('rutas', 0);
    }

    #[Test]
    public function flujo_semanal_completo_desde_las_pantallas(): void
    {
        $ruta = Ruta::factory()->create(['nombre' => 'Curumaní']);
        $comino = $this->producto('Comino molido', 50);
        $canela = $this->producto('Canela', 50);

        // 1. Cargar: surtir 30 paquetes de comino y 10 paquetes + 5 unidades de canela
        $this->actingAs($this->user)->get('/camion/cargar')->assertOk()->assertSee('Comino molido')->assertSee('Curumaní');

        $this->actingAs($this->user)->post('/camion', [
            'ruta_id' => $ruta->id,
            'fecha_salida' => now()->toDateString(),
            'lineas' => [
                $comino->id => ['surtido_p' => 30],
                $canela->id => ['surtido_p' => 10, 'surtido_u' => 5],
            ],
        ])->assertSessionHas('success');

        $viaje = ViajeCamion::firstOrFail();
        $this->assertSame(400, $comino->fresh()->inventario->disponible); // 1000 − 600
        $this->assertSame(795, $canela->fresh()->inventario->disponible); // 1000 − 205

        $this->actingAs($this->user)->get('/camion')->assertOk()->assertSee('El camión está en ruta');
        $this->actingAs($this->user)->get("/camion/{$viaje->id}")->assertOk()->assertSee('30 p')->assertSee('10 p + 5 u');

        // No se puede cargar otro viaje mientras este sigue en ruta
        $this->actingAs($this->user)->get('/camion/cargar')->assertRedirect(route('camion.show', $viaje));

        // 2. Regreso: comino vuelven 8 p, 1 p dañado; canela vuelven 2 p y se regalan 5 u
        $this->actingAs($this->user)->get("/camion/{$viaje->id}/cerrar")->assertOk()->assertSee('Regreso del camión');

        $this->actingAs($this->user)->post("/camion/{$viaje->id}/cerrar", [
            'fecha_regreso' => now()->toDateString(),
            'lineas' => [
                $comino->id => ['regresado_p' => 8, 'danado_p' => 1],
                $canela->id => ['regresado_p' => 2, 'regalado_u' => 5],
            ],
        ])->assertSessionHas('success');

        $detalles = $viaje->fresh()->detalles->keyBy('producto_terminado_id');
        $this->assertSame(420, $detalles[$comino->id]->vendido);  // 600 − 160 − 20 = 21 p
        $this->assertSame(160, $detalles[$canela->id]->vendido);  // 205 − 40 − 5 = 8 p

        $this->actingAs($this->user)->get("/camion/{$viaje->id}")->assertOk()
            ->assertSee('21 p')->assertSee('Vendido según el camión')->assertSee('$696.000'); // 580 u × 1.200

        // 3. Reporte semanal
        $this->actingAs($this->user)->get('/camion/reportes/semanal')->assertOk()
            ->assertSee($viaje->numero)->assertSee('Comino molido')->assertSee('29 p'); // 21 + 8

        // 4. El siguiente cargue parte de lo que regresó
        $this->actingAs($this->user)->get('/camion')->assertOk()->assertSee('El camión está en casa');
        $this->actingAs($this->user)->get('/camion/cargar')->assertOk()->assertSee('8 p');
    }

    #[Test]
    public function errores_del_cargue_y_del_cierre_vuelven_al_formulario(): void
    {
        $ruta = Ruta::factory()->create();
        $comino = $this->producto('Comino', 2);

        $this->actingAs($this->user)->from('/camion/cargar')->post('/camion', [
            'ruta_id' => $ruta->id, 'fecha_salida' => now()->toDateString(),
            'lineas' => [$comino->id => ['surtido_p' => 10]],
        ])->assertRedirect('/camion/cargar')->assertSessionHas('error');

        $this->assertDatabaseCount('viajes_camion', 0);

        $this->actingAs($this->user)->post('/camion', [
            'ruta_id' => $ruta->id, 'fecha_salida' => now()->toDateString(),
            'lineas' => [$comino->id => ['surtido_p' => 1]],
        ]);
        $viaje = ViajeCamion::firstOrFail();

        $this->actingAs($this->user)->from("/camion/{$viaje->id}/cerrar")->post("/camion/{$viaje->id}/cerrar", [
            'fecha_regreso' => now()->toDateString(),
            'lineas' => [$comino->id => ['regresado_p' => 3]],
        ])->assertRedirect("/camion/{$viaje->id}/cerrar")->assertSessionHas('error');

        $this->assertTrue($viaje->fresh()->estaEnRuta());
    }

    #[Test]
    public function anular_un_viaje_desde_la_pantalla(): void
    {
        $ruta = Ruta::factory()->create();
        $comino = $this->producto('Comino', 10);

        $this->actingAs($this->user)->post('/camion', [
            'ruta_id' => $ruta->id, 'fecha_salida' => now()->toDateString(),
            'lineas' => [$comino->id => ['surtido_p' => 4]],
        ]);
        $viaje = ViajeCamion::firstOrFail();

        $this->actingAs($this->user)->post("/camion/{$viaje->id}/anular", ['motivo' => 'Carga equivocada'])
            ->assertSessionHas('success');

        $this->assertSame(ViajeCamion::ANULADO, $viaje->fresh()->estado);
        $this->assertSame(200, $comino->fresh()->inventario->disponible);
    }

    #[Test]
    public function el_formulario_de_movimientos_no_ofrece_los_tipos_del_camion(): void
    {
        $pt = $this->producto('Comino');

        $this->actingAs($this->user)->get('/inventario/movimientos/crear')->assertOk()
            ->assertDontSee('Cargue al camión');

        $this->actingAs($this->user)->post('/inventario/movimientos', [
            'tipo' => 'cargue_camion', 'producto_terminado_id' => $pt->id, 'cantidad' => 1,
        ])->assertSessionHasErrors('tipo');
    }
}

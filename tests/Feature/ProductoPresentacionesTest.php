<?php

namespace Tests\Feature;

use App\Models\InventarioProductoTerminado;
use App\Models\Producto;
use App\Models\ProductoTerminado;
use App\Models\Ruta;
use App\Models\User;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductoPresentacionesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function presentacion(string $nombre, string $linea, float $peso, int $stock = 0, array $extra = []): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create(array_merge(
            ['nombre' => $nombre, 'categoria' => $linea, 'peso_neto' => $peso, 'stock_minimo' => 0, 'activo' => true],
            $extra,
        ));
        InventarioProductoTerminado::create(['producto_terminado_id' => $pt->id, 'disponible' => $stock, 'comprometido' => 0]);

        return $pt;
    }

    #[Test]
    public function la_migracion_agrupa_por_nombre_y_une_los_tamanos(): void
    {
        // Datos «como antes»: presentaciones sueltas sin producto
        $migracion = require database_path('migrations/2026_11_27_000001_create_productos_table.php');
        $migracion->down();

        $insertar = fn (string $codigo, string $nombre, string $cat, float $peso) => DB::table('producto_terminados')->insert([
            'codigo' => $codigo, 'nombre' => $nombre, 'categoria' => $cat, 'presentacion' => 'bolsa',
            'peso_neto' => $peso, 'precio_venta' => 0, 'stock_minimo' => 0, 'activo' => true,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        $insertar('PT-034A', 'COMINO MOLIDO', 'ESP', 20);
        $insertar('PT-034B', 'COMINO MOLIDO', 'B2C', 12);
        $insertar('PT-019A', 'BICARBONATO', 'ESP', 35);
        $insertar('PT-020A', 'BICARBONATO X250', 'ESP', 250);
        $insertar('PT-021A', 'BICARBONATO X450', 'ESP', 450);
        $insertar('PT-040A', 'GUASCAS X20', 'ESP', 5);

        $migracion->up();

        $this->assertSame(['BICARBONATO', 'COMINO MOLIDO', 'GUASCAS X20'], Producto::orderBy('nombre')->pluck('nombre')->all());
        $this->assertSame(0, ProductoTerminado::whereNull('producto_id')->count());

        $bicarbonato = Producto::where('nombre', 'BICARBONATO')->first();
        $this->assertSame(['Especial 35 g', 'Especial 250 g', 'Especial 450 g'], $bicarbonato->presentaciones->map->etiqueta()->all());
        $this->assertSame('BICARBONATO', ProductoTerminado::where('codigo', 'PT-020A')->value('nombre'));
        $this->assertSame(2, Producto::where('nombre', 'COMINO MOLIDO')->first()->presentaciones()->count());
    }

    #[Test]
    public function crea_un_producto_con_dos_presentaciones_y_su_inventario(): void
    {
        $this->post('/inventario/productos-terminados', [
            'nombre' => 'CURRY',
            'presentaciones' => [
                ['categoria' => 'B2C', 'peso_neto' => 12, 'precio_venta' => 13000, 'stock_minimo' => 40, 'activo' => 1],
                ['categoria' => 'ESP', 'peso_neto' => 18, 'precio_venta' => 24000, 'codigo' => 'PT-035A', 'activo' => 1],
                ['categoria' => 'ESP'], // fila vacía: se ignora
            ],
        ])->assertRedirect();

        $curry = Producto::where('nombre', 'CURRY')->firstOrFail();
        $this->assertSame(['B2C 12 g', 'Especial 18 g'], $curry->presentaciones->map->etiqueta()->all());
        $this->assertTrue($curry->presentaciones->every(fn ($p) => $p->nombre === 'CURRY' && $p->inventario !== null));
        $this->assertSame('PT-035A', $curry->presentacionesDe('ESP')->first()->codigo);
        $this->assertStringStartsWith('PT-', $curry->presentacionesDe('B2C')->first()->codigo);
    }

    #[Test]
    public function no_permite_repetir_linea_y_peso_ni_codigos(): void
    {
        $this->presentacion('ANIS', 'ESP', 4, 0, ['codigo' => 'PT-013A']);

        $this->post('/inventario/productos-terminados', [
            'nombre' => 'CLAVO',
            'presentaciones' => [
                ['categoria' => 'ESP', 'peso_neto' => 6],
                ['categoria' => 'ESP', 'peso_neto' => 6],
            ],
        ])->assertSessionHasErrors('presentaciones.1.peso_neto');

        $this->post('/inventario/productos-terminados', [
            'nombre' => 'CLAVO',
            'presentaciones' => [['categoria' => 'ESP', 'peso_neto' => 6, 'codigo' => 'PT-013A']],
        ])->assertSessionHasErrors('presentaciones.0.codigo');

        $this->post('/inventario/productos-terminados', ['nombre' => 'ANIS', 'presentaciones' => [['categoria' => 'B2C', 'peso_neto' => 3]]])
            ->assertSessionHasErrors('nombre');

        $this->assertDatabaseMissing('productos', ['nombre' => 'CLAVO']);
    }

    #[Test]
    public function editar_renombra_agrega_y_quita_presentaciones(): void
    {
        $esp = $this->presentacion('LAUREL', 'ESP', 6, 0);
        $b2c = $this->presentacion('LAUREL', 'B2C', 3, 100);
        $nueva = $this->presentacion('LAUREL', 'ESP', 20, 0);   // nunca usada: se borra al quitarla
        app(MovimientoInventarioService::class)->registrar([
            'origen' => $b2c, 'tipo' => 'ajuste_positivo', 'cantidad' => 5, 'motivo' => 'prueba',
        ]);
        $producto = $esp->producto;

        $this->put("/inventario/productos-terminados/{$producto->id}", [
            'nombre' => 'LAUREL HOJA',
            'activo' => 1,
            'presentaciones' => [
                ['id' => $esp->id, 'categoria' => 'ESP', 'peso_neto' => 6, 'precio_venta' => 24000, 'activo' => 1],
                ['categoria' => 'ESP', 'peso_neto' => 250, 'precio_venta' => 50000, 'activo' => 1],
                // $b2c (con movimientos) y $nueva se quitan
            ],
        ])->assertRedirect(route('inventario.productos-terminados.show', $producto));

        $this->assertSame('LAUREL HOJA', $producto->fresh()->nombre);
        $this->assertSame('LAUREL HOJA', $esp->fresh()->nombre);
        $this->assertFalse($b2c->fresh()->activo);            // con historial: se desactiva
        $this->assertSame(105, $b2c->fresh()->stock_disponible());
        $this->assertNull($nueva->fresh());                   // sin uso: se borra
        $this->assertSame(1, $producto->presentaciones()->where('peso_neto', 250)->count());
    }

    #[Test]
    public function el_listado_muestra_columnas_b2c_y_especial_en_paquetes(): void
    {
        $this->presentacion('COMINO MOLIDO', 'ESP', 20, 245);
        $this->presentacion('COMINO MOLIDO', 'B2C', 12, 60);
        $this->presentacion('ALMENDRA', 'ESP', 25, 0);

        $respuesta = $this->get('/inventario/productos-terminados')->assertOk();

        $respuesta->assertSeeInOrder(['B2C', 'Especial']);
        $respuesta->assertSeeInOrder(['COMINO MOLIDO', '3 p', '12 p + 5 u']);
        $this->assertSame(1, substr_count($respuesta->getContent(), '>COMINO MOLIDO</a>')); // una sola fila
    }

    #[Test]
    public function el_listado_trae_el_buscador_con_nombre_y_codigos(): void
    {
        $this->presentacion('AZÚCAR DE LECHE', 'ESP', 15, 0, ['codigo' => 'PT-016A']);
        $this->presentacion('AZÚCAR DE LECHE', 'B2C', 8, 0, ['codigo' => 'PT-016B']);

        $this->get('/inventario/productos-terminados?q=azucar')->assertOk()
            ->assertSee('id="buscador" value="azucar"', false)
            ->assertSee('data-buscar="azucar de leche pt-016b pt-016a"', false)
            ->assertSee('1 productos');
    }

    #[Test]
    public function el_detalle_muestra_las_presentaciones_y_el_kardex_de_la_elegida(): void
    {
        $esp = $this->presentacion('CURCUMA', 'ESP', 9, 60);
        $b2c = $this->presentacion('CURCUMA', 'B2C', 6, 120);

        $this->get("/inventario/productos-terminados/{$esp->producto_id}")
            ->assertOk()
            ->assertSee('Especial')->assertSee('B2C')
            ->assertSee('3 p')->assertSee('6 p')
            ->assertSee('Kardex · B2C 6 g');

        $this->get("/inventario/productos-terminados/{$esp->producto_id}?presentacion={$esp->id}")
            ->assertSee('Kardex · Especial 9 g');
    }

    #[Test]
    public function los_selectores_agrupan_las_presentaciones_por_producto(): void
    {
        $pt = $this->presentacion('ALBAHACA', 'ESP', 5, 0);
        $pt->receta()->create(['materia_prima_id' => \App\Models\MateriaPrima::factory()->create()->id, 'gramos_por_unidad' => 5]);
        $this->presentacion('ALBAHACA', 'B2C', 4, 0);

        $this->get('/inventario/produccion/crear')->assertOk()
            ->assertSee('<optgroup label="ALBAHACA">', false)
            ->assertSeeInOrder(['ALBAHACA · B2C 4 g', 'ALBAHACA · Especial 5 g']);
    }

    #[Test]
    public function el_cargue_del_camion_agrupa_por_producto(): void
    {
        $this->presentacion('JENGIBRE', 'ESP', 10, 100);
        $this->presentacion('JENGIBRE', 'B2C', 4, 100);
        Ruta::factory()->create();

        $html = $this->get('/camion/cargar')->assertOk()->getContent();

        $this->assertSame(1, substr_count($html, 'class="fila-grupo" data-grupo="jengibre"'));
        $this->assertSame(2, substr_count($html, 'fila-producto" data-grupo="jengibre"'));
    }

    #[Test]
    public function eliminar_y_activar_afecta_al_producto_completo(): void
    {
        $esp = $this->presentacion('ALUMBRE', 'ESP', 30, 0);
        $b2c = $this->presentacion('ALUMBRE', 'B2C', 20, 0);
        $producto = $esp->producto;

        $this->post("/inventario/productos-terminados/{$producto->id}/toggle")->assertRedirect();
        $this->assertFalse($esp->fresh()->activo);
        $this->assertFalse($b2c->fresh()->activo);

        $this->delete("/inventario/productos-terminados/{$producto->id}")->assertRedirect();
        $this->assertNull($producto->fresh());
        $this->assertDatabaseMissing('producto_terminados', ['id' => $b2c->id]);
    }

    #[Test]
    public function eliminar_borra_el_producto_con_todo_su_historial(): void
    {
        $esp = $this->presentacion('NUEZ', 'ESP', 10, 100);
        $b2c = $this->presentacion('NUEZ', 'B2C', 5, 0);
        $otro = $this->presentacion('CLAVO', 'ESP', 6, 100);
        $producto = $esp->producto;
        $mp = \App\Models\MateriaPrima::factory()->create();
        $esp->receta()->create(['materia_prima_id' => $mp->id, 'gramos_por_unidad' => 10]);

        // Historial: ajuste anulado (con reversión), viaje cerrado y conteo
        $movimientos = app(MovimientoInventarioService::class);
        $ajuste = $movimientos->registrar(['origen' => $esp, 'tipo' => 'ajuste_positivo', 'cantidad' => 20, 'motivo' => 'prueba']);
        $movimientos->anular($ajuste, 'error');
        $camion = app(\App\Services\ViajeCamionService::class);
        $viaje = $camion->cargar(['ruta_id' => Ruta::factory()->create()->id, 'lineas' => [$esp->id => ['surtido' => 40], $otro->id => ['surtido' => 20]]]);
        $camion->cerrar($viaje, ['lineas' => [$esp->id => ['regresado' => 0], $otro->id => ['regresado' => 0]]]);

        $this->get("/inventario/productos-terminados/{$producto->id}/eliminar")->assertOk()
            ->assertSee('Se borrará todo el historial')
            ->assertSee('movimientos del kardex')
            ->assertSee('1 viaje del camión');

        // Sin la casilla no borra nada
        $this->delete("/inventario/productos-terminados/{$producto->id}")->assertSessionHasErrors('confirmar');
        $this->assertNotNull($producto->fresh());

        $this->delete("/inventario/productos-terminados/{$producto->id}", ['confirmar' => 1])
            ->assertRedirect(route('inventario.productos-terminados.index'));

        $this->assertNull($producto->fresh());
        $this->assertDatabaseMissing('producto_terminados', ['id' => $esp->id]);
        $this->assertDatabaseMissing('producto_terminados', ['id' => $b2c->id]);
        $this->assertSame(0, \App\Models\MovimientoInventario::where('origen_type', ProductoTerminado::class)->whereIn('origen_id', [$esp->id, $b2c->id])->count());
        $this->assertDatabaseMissing('detalle_viaje_camion', ['producto_terminado_id' => $esp->id]);
        $this->assertDatabaseMissing('detalle_receta', ['producto_terminado_id' => $esp->id]);

        // Lo de los demás productos queda intacto
        $this->assertDatabaseHas('detalle_viaje_camion', ['producto_terminado_id' => $otro->id, 'viaje_camion_id' => $viaje->id]);
        $this->assertSame(80, $otro->fresh()->stock_disponible());
    }

    #[Test]
    public function no_elimina_un_producto_cargado_en_el_camion_en_ruta(): void
    {
        $esp = $this->presentacion('PIMIENTA', 'ESP', 10, 100);
        $viaje = app(\App\Services\ViajeCamionService::class)
            ->cargar(['ruta_id' => Ruta::factory()->create()->id, 'lineas' => [$esp->id => ['surtido' => 40]]]);

        $this->get("/inventario/productos-terminados/{$esp->producto_id}/eliminar")->assertOk()
            ->assertSee('No se puede eliminar ahora')
            ->assertSee($viaje->numero);

        $this->delete("/inventario/productos-terminados/{$esp->producto_id}", ['confirmar' => 1])
            ->assertSessionHas('error');
        $this->assertNotNull($esp->fresh());
    }

    #[Test]
    public function ajustar_stock_registra_la_diferencia_en_el_kardex(): void
    {
        $esp = $this->presentacion('ANIS', 'ESP', 4, 245); // 12 p + 5 u
        $url = "/inventario/productos-terminados/presentaciones/{$esp->id}/ajustar-stock";

        $this->get("/inventario/productos-terminados/{$esp->producto_id}?presentacion={$esp->id}")->assertOk()
            ->assertSee('Ajustar stock')
            ->assertSee('name="paquetes" min="0" step="1" class="input-glass"', false);

        // Baja a 10 p: salida de 45 u
        $this->post($url, ['paquetes' => 10, 'sueltas' => 0, 'motivo' => 'Conteo de bodega'])
            ->assertRedirect(route('inventario.productos-terminados.show', [$esp->producto_id, 'presentacion' => $esp->id]))
            ->assertSessionHas('success');
        $this->assertSame(200, $esp->fresh()->stock_disponible());
        $mov = $esp->movimientos()->latest('id')->first();
        $this->assertSame('ajuste_negativo', $mov->tipo);
        $this->assertSame(45, (int) $mov->cantidad);
        $this->assertSame(200, (int) $mov->saldo);
        $this->assertSame('AJUSTE-MANUAL', $mov->documento);
        $this->assertSame('Conteo de bodega', $mov->motivo);

        // Sube a 15 p + 3 u: entrada de 103 u
        $this->post($url, ['paquetes' => 15, 'sueltas' => 3, 'motivo' => 'Se encontraron cajas']);
        $this->assertSame(303, $esp->fresh()->stock_disponible());
        $this->assertSame('ajuste_positivo', $esp->movimientos()->latest('id')->value('tipo'));

        // Mismo valor: no registra nada
        $this->post($url, ['paquetes' => 15, 'sueltas' => 3, 'motivo' => 'igual']);
        $this->assertSame(2, $esp->movimientos()->count());

        // Motivo obligatorio y sin negativos
        $this->post($url, ['paquetes' => 1])->assertSessionHasErrors('motivo');
        $this->post($url, ['paquetes' => -1, 'motivo' => 'x'])->assertSessionHasErrors('paquetes');
        $this->assertSame(303, $esp->fresh()->stock_disponible());
    }

    #[Test]
    public function el_listado_siempre_ofrece_eliminar(): void
    {
        $pt = $this->presentacion('ROMERO', 'ESP', 8, 100);

        $this->get('/inventario/productos-terminados')->assertOk()
            ->assertSee(route('inventario.productos-terminados.eliminar', $pt->producto_id), false);
    }
}

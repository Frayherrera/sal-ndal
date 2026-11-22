<?php

namespace Tests\Unit;

use App\Models\CategoriaGasto;
use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\InventarioProductoTerminado;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
use App\Models\Ruta;
use App\Models\User;
use App\Models\Venta;
use App\Models\ViajeCamion;
use App\Services\ViajeCamionService;
use App\Support\Paquetes;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ViajeCamionServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private ViajeCamionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->service = app(ViajeCamionService::class);
    }

    /** Producto con stock en bodega expresado en paquetes; precio por paquete. */
    private function producto(string $nombre, int $paquetesBodega = 50, float $precioPaquete = 24000): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create(['nombre' => $nombre, 'precio_venta' => $precioPaquete, 'activo' => true]);
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => $paquetesBodega * 20,
            'comprometido' => 0,
        ]);

        return $pt;
    }

    private function bodega(ProductoTerminado $pt): int
    {
        return $pt->fresh()->inventario->disponible;
    }

    #[Test]
    public function convierte_paquetes_y_unidades(): void
    {
        $this->assertSame(245, Paquetes::aUnidades(12, 5));
        $this->assertSame(240, Paquetes::aUnidades('12', ''));
        $this->assertSame([12, 5], Paquetes::separar(245));
        $this->assertSame('12 p + 5 u', Paquetes::texto(245));
        $this->assertSame('12 p', Paquetes::texto(240));
        $this->assertSame('5 u', Paquetes::texto(5));
        $this->assertSame('0', Paquetes::texto(0));
        $this->assertSame('0', Paquetes::texto(null));
    }

    #[Test]
    public function el_primer_cargue_descuenta_bodega_y_deja_el_viaje_en_ruta(): void
    {
        $ruta = Ruta::factory()->create(['nombre' => 'Pelaya']);
        $comino = $this->producto('Comino', 50);

        $viaje = $this->service->cargar([
            'ruta_id' => $ruta->id,
            'fecha_salida' => now()->toDateString(),
            'lineas' => [$comino->id => ['surtido' => 30 * 20]],
        ]);

        $this->assertSame('VJE-00001', $viaje->numero);
        $this->assertSame(ViajeCamion::EN_RUTA, $viaje->estado);
        $this->assertSame(20 * 20, $this->bodega($comino)); // 50 − 30 paquetes

        $detalle = $viaje->detalles->first();
        $this->assertSame(0, $detalle->inicial);
        $this->assertSame(600, $detalle->surtido);
        $this->assertSame(600, $detalle->cargado);
        $this->assertSame(1200.0, (float) $detalle->precio_unidad); // 24.000 / 20

        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'cargue_camion',
            'direccion' => 'salida',
            'cantidad' => 600,
            'documento' => 'VJE-00001',
            'viaje_camion_id' => $viaje->id,
        ]);
        $this->assertSame([$comino->id => 600], $this->service->stockCamion());
    }

    #[Test]
    public function cerrar_calcula_lo_vendido_sin_tocar_bodega(): void
    {
        $ruta = Ruta::factory()->create();
        $comino = $this->producto('Comino', 50);
        $viaje = $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 600]]]);

        $cerrado = $this->service->cerrar($viaje, [
            'fecha_regreso' => now()->toDateString(),
            'lineas' => [$comino->id => [
                'regresado' => Paquetes::aUnidades(8, 5),
                'danado' => Paquetes::aUnidades(1),
                'regalado' => 5,
            ]],
        ]);

        $d = $cerrado->detalles->first();
        // 600 − 165 − 20 − 5 = 410 unidades = 20 p + 10 u
        $this->assertSame(410, $d->vendido);
        $this->assertSame(ViajeCamion::CERRADO, $cerrado->estado);
        $this->assertSame(400, $this->bodega($comino)); // bodega no cambia al cerrar
        $this->assertSame(492000.0, $d->valorVendido()); // 410 × 1.200
        $this->assertSame(30000.0, $d->valorMerma());    // 25 × 1.200

        // Lo que regresó queda en el camión
        $this->assertSame([$comino->id => 165], $this->service->stockCamion());
    }

    #[Test]
    public function no_deja_que_lo_anotado_supere_lo_que_salio(): void
    {
        $ruta = Ruta::factory()->create();
        $comino = $this->producto('Comino');
        $viaje = $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 100]]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/supera lo que salió/');

        $this->service->cerrar($viaje, ['lineas' => [$comino->id => ['regresado' => 90, 'danado' => 20]]]);
    }

    #[Test]
    public function el_siguiente_viaje_parte_de_lo_que_regreso_y_permite_bajar_a_bodega(): void
    {
        $r1 = Ruta::factory()->create(['nombre' => 'Ruta A', 'orden' => 1]);
        $r2 = Ruta::factory()->create(['nombre' => 'Ruta B', 'orden' => 2]);
        $comino = $this->producto('Comino', 50);
        $canela = $this->producto('Canela', 50);

        $v1 = $this->service->cargar(['ruta_id' => $r1->id, 'lineas' => [
            $comino->id => ['surtido' => 200], $canela->id => ['surtido' => 100],
        ]]);
        $this->service->cerrar($v1, ['lineas' => [
            $comino->id => ['regresado' => 60], $canela->id => ['regresado' => 40],
        ]]);

        // Sugiere la siguiente ruta de la rotación
        $this->assertSame($r2->id, $this->service->rutaSugerida()->id);

        $v2 = $this->service->cargar(['ruta_id' => $r2->id, 'lineas' => [
            $comino->id => ['surtido' => 100],
            $canela->id => ['bajado' => 40], // se baja toda la canela
        ]]);

        $detalles = $v2->detalles->keyBy('producto_terminado_id');
        $this->assertSame(60, $detalles[$comino->id]->inicial);
        $this->assertSame(160, $detalles[$comino->id]->cargado);
        $this->assertSame(0, $detalles[$canela->id]->cargado);

        $this->assertSame(1000 - 200 - 100, $this->bodega($comino));
        $this->assertSame(1000 - 100 + 40, $this->bodega($canela)); // lo bajado vuelve a bodega
        $this->assertDatabaseHas('movimientos_inventario', ['tipo' => 'descargue_camion', 'direccion' => 'entrada', 'cantidad' => 40]);

        // Después de la última ruta vuelve a la primera
        $this->service->cerrar($v2, ['lineas' => [$comino->id => ['regresado' => 0]]]);
        $this->assertSame($r1->id, $this->service->rutaSugerida()->id);
    }

    #[Test]
    public function validaciones_del_cargue(): void
    {
        $ruta = Ruta::factory()->create();
        $comino = $this->producto('Comino', 5);

        // Camión vacío
        try {
            $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => []]);
            $this->fail('Debió rechazar un camión vacío.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('vacío', $e->getMessage());
        }

        // Más surtido que el stock de bodega
        try {
            $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 200]]]);
            $this->fail('Debió rechazar surtir más de lo que hay en bodega.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Stock insuficiente', $e->getMessage());
        }

        $this->assertDatabaseCount('viajes_camion', 0); // se revirtió todo

        // Bajar más de lo que hay en el camión
        try {
            $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 20, 'bajado' => 10]]]);
            $this->fail('Debió rechazar bajar lo que no está en el camión.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No se pueden bajar', $e->getMessage());
        }

        // Solo un viaje en ruta a la vez
        $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 20]]]);
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Ya hay un viaje en ruta/');
        $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 20]]]);
    }

    #[Test]
    public function anular_un_viaje_devuelve_el_surtido_a_bodega(): void
    {
        $ruta = Ruta::factory()->create();
        $comino = $this->producto('Comino', 50);
        $viaje = $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 300]]]);

        $this->assertSame(700, $this->bodega($comino));

        $anulado = $this->service->anular($viaje, 'Se cargó mal');

        $this->assertSame(ViajeCamion::ANULADO, $anulado->estado);
        $this->assertSame(1000, $this->bodega($comino));
        $this->assertSame(1, MovimientoInventario::where('viaje_camion_id', $viaje->id)->where('tipo', 'anulacion_reversion')->count());
        $this->assertNull($this->service->viajeEnRuta());

        $this->expectException(RuntimeException::class);
        $this->service->anular($anulado, 'Otra vez');
    }

    #[Test]
    public function promedio_vendido_por_ruta_para_sugerir_el_surtido(): void
    {
        $ruta = Ruta::factory()->create();
        $otra = Ruta::factory()->create(['orden' => 2]);
        $comino = $this->producto('Comino', 100);

        // Viaje 1 (ruta): sale con 300, regresan 200 → vende 100
        $v1 = $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 300]]]);
        $this->service->cerrar($v1, ['lineas' => [$comino->id => ['regresado' => 200]]]);

        // Viaje 2 (otra ruta): sale con los 200 que venían, no regresa nada → vende 200
        $v2 = $this->service->cargar(['ruta_id' => $otra->id, 'lineas' => []]);
        $this->service->cerrar($v2, ['lineas' => [$comino->id => ['regresado' => 0]]]);

        // Viaje 3 (ruta): sale con 500, regresan 200 → vende 300
        $v3 = $this->service->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 500]]]);
        $this->service->cerrar($v3, ['lineas' => [$comino->id => ['regresado' => 200]]]);

        $this->assertSame([$comino->id => 200], $this->service->promedioVendido($ruta)); // (100 + 300) / 2
        $this->assertSame([$comino->id => 200], $this->service->promedioVendido($otra));
    }

    #[Test]
    public function resultado_del_viaje_cruza_ventas_de_la_ruta_y_gastos(): void
    {
        $ruta = Ruta::factory()->create(['ciudades' => ['Pelaya']]);
        $comino = $this->producto('Comino', 50, 24000);

        $viaje = $this->service->cargar([
            'ruta_id' => $ruta->id,
            'fecha_salida' => now()->subDays(3)->toDateString(),
            'lineas' => [$comino->id => ['surtido' => 200]],
        ]);
        $this->service->cerrar($viaje, ['lineas' => [$comino->id => ['regresado' => 100]]]); // vende 100 u = $120.000

        $dePelaya = Cliente::factory()->create(['ciudad' => 'PELAYA']);
        $deOtra = Cliente::factory()->create(['ciudad' => 'Curumaní']);
        Venta::factory()->create(['cliente_id' => $dePelaya->id, 'fecha' => now()->subDay(), 'total' => 110000, 'subtotal' => 110000]);
        Venta::factory()->create(['cliente_id' => $deOtra->id, 'fecha' => now()->subDay(), 'total' => 999000, 'subtotal' => 999000]);

        $rutaGastos = CategoriaGasto::factory()->create(['nombre' => 'Ruta']);
        Gasto::factory()->create(['categoria_gasto_id' => $rutaGastos->id, 'fecha' => now()->subDays(2)->toDateString(), 'monto' => 70000]);
        Gasto::factory()->create(['fecha' => now()->subDays(2)->toDateString(), 'monto' => 5000]); // otra categoría

        $r = $this->service->resultado($viaje->fresh());

        $this->assertSame(120000.0, $r['vendido']);
        $this->assertSame(110000.0, $r['ventas_registradas']);
        $this->assertSame(1, $r['ventas_count']);
        $this->assertSame(-10000.0, $r['diferencia']);
        $this->assertSame(70000.0, $r['gastos_ruta']);
        $this->assertSame(40000.0, $r['resultado']);
        $this->assertTrue($r['filtra_por_ciudad']);
    }
}

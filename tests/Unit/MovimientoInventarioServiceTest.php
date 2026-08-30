<?php

namespace Tests\Unit;

use App\Models\ConfiguracionInventario;
use App\Models\InventarioMateriaPrima;
use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class MovimientoInventarioServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected MovimientoInventarioService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MovimientoInventarioService;
    }

    #[Test]
    public function registra_una_compra_de_materia_prima(): void
    {
        $mp = MateriaPrima::factory()->create(['unidad_base' => 'kg']);

        $mov = $this->service->registrar([
            'tipo' => 'compra_recepcion',
            'origen' => $mp,
            'cantidad' => 5000, // 5 kg en gramos
            'costo_total' => 50000,
            'fecha' => now(),
        ]);

        $this->assertSame('entrada', $mov->direccion);
        $this->assertSame(5000, $mov->saldo);
        $this->assertSame(5000, $mp->fresh()->inventario->stock_gramos);
        $this->assertSame('compra_recepcion', $mov->tipo);
    }

    #[Test]
    public function registro_de_venta_disminuye_stock(): void
    {
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 100,
            'comprometido' => 0,
        ]);

        $this->service->registrar([
            'tipo' => 'venta_despacho',
            'origen' => $pt,
            'cantidad' => 30,
            'fecha' => now(),
        ]);

        $this->assertSame(70, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function no_permite_venta_sin_stock_suficiente(): void
    {
        ConfiguracionInventario::definir('permitir_stock_negativo', false);

        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 10,
            'comprometido' => 0,
        ]);

        $this->expectException(RuntimeException::class);

        $this->service->registrar([
            'tipo' => 'venta_despacho',
            'origen' => $pt,
            'cantidad' => 50,
            'fecha' => now(),
        ]);
    }

    #[Test]
    public function permite_stock_negativo_si_esta_configurado(): void
    {
        ConfiguracionInventario::definir('permitir_stock_negativo', true);

        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 0,
            'comprometido' => 0,
        ]);

        $this->service->registrar([
            'tipo' => 'venta_despacho',
            'origen' => $pt,
            'cantidad' => 20,
            'fecha' => now(),
        ]);

        $this->assertSame(-20, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function la_produccion_aumenta_stock_del_producto(): void
    {
        $pt = ProductoTerminado::factory()->create();

        $mov = $this->service->registrar([
            'tipo' => 'producto_producido',
            'origen' => $pt,
            'cantidad' => 200,
            'fecha' => now(),
        ]);

        $this->assertSame('entrada', $mov->direccion);
        $this->assertSame(200, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function consumo_de_produccion_disminuye_materia_prima(): void
    {
        $mp = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create([
            'materia_prima_id' => $mp->id,
            'stock_gramos' => 10000,
            'costo_promedio' => 0,
        ]);

        $this->service->registrar([
            'tipo' => 'consumo_produccion',
            'origen' => $mp,
            'cantidad' => 2500,
            'fecha' => now(),
        ]);

        $this->assertSame(7500, $mp->fresh()->inventario->stock_gramos);
    }

    #[Test]
    public function registrar_devolucion_entrada_aumenta_stock(): void
    {
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 50,
            'comprometido' => 0,
        ]);

        $this->service->registrarDevolucion($pt, 10, 'entrada', 'Cliente devolvió');

        $this->assertSame(60, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function anular_revierte_el_stock_y_crea_reversion(): void
    {
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 100,
            'comprometido' => 0,
        ]);

        $original = $this->service->registrar([
            'tipo' => 'venta_despacho',
            'origen' => $pt,
            'cantidad' => 30,
            'fecha' => now(),
        ]);

        $this->assertSame(70, $pt->fresh()->inventario->disponible);

        $reversion = $this->service->anular($original, 'Venta con error');

        $this->assertSame('anulacion_reversion', $reversion->tipo);
        $this->assertSame('entrada', $reversion->direccion);
        $this->assertSame($original->id, $reversion->movimiento_original_id);
        $this->assertSame(100, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function no_se_puede_anular_una_reversion(): void
    {
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 100,
            'comprometido' => 0,
        ]);

        $original = $this->service->registrar([
            'tipo' => 'venta_despacho',
            'origen' => $pt,
            'cantidad' => 30,
            'fecha' => now(),
        ]);

        $reversion = $this->service->anular($original, 'Error');

        $this->expectException(RuntimeException::class);
        $this->service->anular($reversion, 'Doble anulación');
    }

    #[Test]
    public function la_cantidad_debe_ser_mayor_a_cero(): void
    {
        $mp = MateriaPrima::factory()->create();

        $this->expectException(RuntimeException::class);

        $this->service->registrar([
            'tipo' => 'compra_recepcion',
            'origen' => $mp,
            'cantidad' => 0,
            'fecha' => now(),
        ]);
    }
}

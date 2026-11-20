<?php

namespace Tests\Unit;

use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Services\ConteoFisicoService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class ConteoFisicoServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected ConteoFisicoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ConteoFisicoService;
    }

    #[Test]
    public function crea_un_conteo_en_borrador(): void
    {
        $conteo = $this->service->crear([
            'tipo' => 'materia_prima',
            'fecha_conteo' => now(),
        ]);

        $this->assertSame('borrador', $conteo->estado);
        $this->assertStringStartsWith('CF-', $conteo->codigo);
    }

    #[Test]
    public function registrar_detalle_calcula_diferencia(): void
    {
        $mp = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create([
            'materia_prima_id' => $mp->id,
            'stock_gramos' => 10000,
            'costo_promedio' => 0,
        ]);

        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);

        $this->service->registrarDetalle($conteo, [
            ['materia_prima_id' => $mp->id, 'stock_sistema' => 10000, 'cantidad_fisica' => 9800, 'motivo' => 'Merma'],
        ]);

        $detalle = $conteo->detalles()->first();
        $this->assertSame(-200, $detalle->diferencia);
    }

    #[Test]
    public function no_completa_un_conteo_sin_detalles(): void
    {
        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);

        $this->expectException(RuntimeException::class);
        $this->service->completar($conteo);
    }

    #[Test]
    public function aprobar_genera_ajustes_y_actualiza_stock(): void
    {
        $mp = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create([
            'materia_prima_id' => $mp->id,
            'stock_gramos' => 10000,
            'costo_promedio' => 0,
        ]);

        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);
        $this->service->registrarDetalle($conteo, [
            ['materia_prima_id' => $mp->id, 'stock_sistema' => 10000, 'cantidad_fisica' => 10500, 'motivo' => 'Sobrante'],
        ]);
        $this->service->completar($conteo);
        $this->service->aprobar($conteo);

        $conteo->refresh();
        $this->assertSame('aprobado', $conteo->estado);

        // Ajuste positivo de 500 g
        $this->assertSame(10500, $mp->fresh()->inventario->stock_gramos);
        $this->assertSame(1, MovimientoInventario::where('conteo_fisico_id', $conteo->id)->count());
        $this->assertSame('ajuste_positivo', MovimientoInventario::where('conteo_fisico_id', $conteo->id)->first()->tipo);
    }

    #[Test]
    public function el_stock_del_sistema_lo_calcula_el_servidor(): void
    {
        $mp = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => 8000, 'costo_promedio' => 0]);

        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);

        // El stock_sistema enviado (0) se ignora: se toma del inventario
        $this->service->registrarDetalle($conteo, [
            ['materia_prima_id' => $mp->id, 'stock_sistema' => 0, 'cantidad_fisica' => 7500],
        ]);

        $detalle = $conteo->detalles()->first();
        $this->assertSame(8000, $detalle->stock_sistema);
        $this->assertSame(-500, $detalle->diferencia);
    }

    #[Test]
    public function el_ajuste_aplica_la_diferencia_aunque_haya_movimientos_despues_del_conteo(): void
    {
        $mp = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => 10000, 'costo_promedio' => 0]);

        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);
        $this->service->registrarDetalle($conteo, [
            ['materia_prima_id' => $mp->id, 'cantidad_fisica' => 9700, 'motivo' => 'Merma'],
        ]);
        $this->service->completar($conteo);

        // Entre el conteo y la aprobación entra una compra de 2 kg
        app(\App\Services\MovimientoInventarioService::class)->registrar([
            'tipo' => 'compra_recepcion',
            'origen' => $mp,
            'cantidad' => 2000,
        ]);

        $this->actingAs(\App\Models\User::factory()->create());
        $this->service->aprobar($conteo->fresh());

        // 10000 + 2000 (compra) − 300 (merma del conteo) = 11700
        $this->assertSame(11700, $mp->fresh()->inventario->stock_gramos);
    }

    #[Test]
    public function rechaza_lineas_sin_articulo_duplicadas_o_sin_cantidad(): void
    {
        $mp = MateriaPrima::factory()->create();
        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);

        $casos = [
            'sin artículo' => [['materia_prima_id' => null, 'cantidad_fisica' => 10]],
            'duplicada' => [
                ['materia_prima_id' => $mp->id, 'cantidad_fisica' => 10],
                ['materia_prima_id' => $mp->id, 'cantidad_fisica' => 20],
            ],
            'sin cantidad' => [['materia_prima_id' => $mp->id, 'cantidad_fisica' => '']],
            'negativa' => [['materia_prima_id' => $mp->id, 'cantidad_fisica' => -5]],
            'vacía' => [],
        ];

        foreach ($casos as $nombre => $lineas) {
            try {
                $this->service->registrarDetalle($conteo, $lineas);
                $this->fail("Debió rechazar la línea: {$nombre}");
            } catch (RuntimeException) {
                $this->assertSame(0, $conteo->detalles()->count(), $nombre);
            }
        }
    }

    #[Test]
    public function un_conteo_completado_ya_no_se_edita(): void
    {
        $mp = MateriaPrima::factory()->create();
        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);
        $this->service->registrarDetalle($conteo, [['materia_prima_id' => $mp->id, 'cantidad_fisica' => 100]]);
        $this->service->completar($conteo);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/borrador/');

        $this->service->registrarDetalle($conteo->fresh(), [['materia_prima_id' => $mp->id, 'cantidad_fisica' => 200]]);
    }

    #[Test]
    public function solo_aprueba_conteos_completados(): void
    {
        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);

        $this->expectException(RuntimeException::class);
        $this->service->aprobar($conteo);
    }
}

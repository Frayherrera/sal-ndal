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
    public function solo_aprueba_conteos_completados(): void
    {
        $conteo = $this->service->crear(['tipo' => 'materia_prima', 'fecha_conteo' => now()]);

        $this->expectException(RuntimeException::class);
        $this->service->aprobar($conteo);
    }
}

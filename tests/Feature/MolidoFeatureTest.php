<?php

namespace Tests\Feature;

use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MolidoFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function molidoConIngredientes(array $lineas, string $unidad = 'kg', int $stock = 0): MateriaPrima
    {
        $molido = MateriaPrima::factory()->create(['es_molido' => true, 'unidad_base' => $unidad]);
        InventarioMateriaPrima::create(['materia_prima_id' => $molido->id, 'stock_gramos' => $stock, 'costo_promedio' => 0]);

        foreach ($lineas as $ingId => $gramos) {
            $molido->detalleMolido()->create(['ingrediente_id' => $ingId, 'gramos_por_kg' => $gramos]);
        }

        return $molido;
    }

    #[Test]
    public function crear_molido_guarda_sus_ingredientes(): void
    {
        $ingrediente = MateriaPrima::factory()->create();

        $this->actingAs($this->user)
            ->post('/inventario/materia-prima', [
                'nombre' => 'Comino molido',
                'unidad_base' => 'kg',
                'es_molido' => '1',
                'lineas' => [
                    ['ingrediente_id' => $ingrediente->id, 'gramos_por_kg' => 1050],
                ],
            ])
            ->assertRedirect(route('inventario.materia-prima.index'))
            ->assertSessionHas('success');

        $molido = MateriaPrima::where('nombre', 'Comino molido')->firstOrFail();
        $this->assertTrue($molido->es_molido);
        $this->assertCount(1, $molido->detalleMolido);
        $this->assertSame($ingrediente->id, $molido->detalleMolido->first()->ingrediente_id);
        $this->assertEquals(1050, $molido->detalleMolido->first()->gramos_por_kg);
    }

    #[Test]
    public function crear_molido_sin_ingredientes_es_rechazado(): void
    {
        $this->actingAs($this->user)
            ->post('/inventario/materia-prima', [
                'nombre' => 'Molido sin receta',
                'unidad_base' => 'kg',
                'es_molido' => '1',
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('materias_primas', ['nombre' => 'Molido sin receta']);
    }

    #[Test]
    public function producir_molido_descuenta_ingredientes_y_suma_stock(): void
    {
        $ingrediente = MateriaPrima::factory()->create(['unidad_base' => 'kg']);
        InventarioMateriaPrima::create(['materia_prima_id' => $ingrediente->id, 'stock_gramos' => 100000, 'costo_promedio' => 0]);

        $molido = $this->molidoConIngredientes([$ingrediente->id => 1050]);

        $this->actingAs($this->user)
            ->post("/inventario/materia-prima/{$molido->id}/producir-molido", [
                'cantidad' => '2',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('inventario.materia-prima.show', $molido))
            ->assertSessionHas('success');

        // 2 kg × 1050 g/kg = 2100 g consumidos → 100000 - 2100 = 97900
        $this->assertSame(97900, $ingrediente->fresh()->inventario->stock_gramos);
        // +2000 g de molido
        $this->assertSame(2000, $molido->fresh()->inventario->stock_gramos);

        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'consumo_produccion',
            'origen_type' => $ingrediente->getMorphClass(),
            'origen_id' => $ingrediente->id,
            'cantidad' => 2100,
            'direccion' => 'salida',
        ]);
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'producto_producido',
            'origen_type' => $molido->getMorphClass(),
            'origen_id' => $molido->id,
            'cantidad' => 2000,
            'direccion' => 'entrada',
        ]);
    }

    #[Test]
    public function rechaza_producir_molido_sin_ingredientes(): void
    {
        $molido = $this->molidoConIngredientes([]);

        $this->actingAs($this->user)
            ->post("/inventario/materia-prima/{$molido->id}/producir-molido", [
                'cantidad' => '1',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertSame(0, $molido->fresh()->inventario->stock_gramos);
    }

    #[Test]
    public function rechaza_producir_molido_con_stock_insuficiente(): void
    {
        $ingrediente = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create(['materia_prima_id' => $ingrediente->id, 'stock_gramos' => 100, 'costo_promedio' => 0]);
        $molido = $this->molidoConIngredientes([$ingrediente->id => 50]); // 5 kg → 250 g

        $this->actingAs($this->user)
            ->post("/inventario/materia-prima/{$molido->id}/producir-molido", [
                'cantidad' => '5',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHas('error');

        $this->assertSame(100, $ingrediente->fresh()->inventario->stock_gramos);
        $this->assertSame(0, $molido->fresh()->inventario->stock_gramos);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[Test]
    public function actualizar_molido_reemplaza_sus_ingredientes(): void
    {
        $ingA = MateriaPrima::factory()->create();
        $ingB = MateriaPrima::factory()->create();
        $molido = $this->molidoConIngredientes([$ingA->id => 500]);

        $this->actingAs($this->user)
            ->put("/inventario/materia-prima/{$molido->id}", [
                'nombre' => $molido->nombre,
                'unidad_base' => 'kg',
                'es_molido' => '1',
                'lineas' => [
                    ['ingrediente_id' => $ingB->id, 'gramos_por_kg' => 700],
                ],
            ])
            ->assertRedirect(route('inventario.materia-prima.index'));

        $molido->refresh();
        $this->assertCount(1, $molido->detalleMolido);
        $this->assertSame($ingB->id, $molido->detalleMolido->first()->ingrediente_id);
    }

    #[Test]
    public function la_vista_de_producir_molido_se_renders(): void
    {
        $ingrediente = MateriaPrima::factory()->create(['nombre' => 'Comino entero']);
        $molido = $this->molidoConIngredientes([$ingrediente->id => 500]);

        $this->actingAs($this->user)
            ->get("/inventario/materia-prima/{$molido->id}/producir-molido")
            ->assertOk()
            ->assertSee('Comino entero');
    }

    #[Test]
    public function la_vista_de_editar_molido_se_renders(): void
    {
        $molido = $this->molidoConIngredientes([MateriaPrima::factory()->create()->id => 500]);

        $this->actingAs($this->user)
            ->get("/inventario/materia-prima/{$molido->id}/edit")
            ->assertOk()
            ->assertSee('produce a partir de otros ingredientes');
    }
}

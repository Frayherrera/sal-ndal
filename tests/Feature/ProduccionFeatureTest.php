<?php

namespace Tests\Feature;

use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProduccionFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function productoConReceta(array $lineas): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create();
        foreach ($lineas as $mpId => $gramos) {
            $pt->receta()->create(['materia_prima_id' => $mpId, 'gramos_por_unidad' => $gramos]);
        }

        return $pt;
    }

    #[Test]
    public function registrar_produccion_descuenta_materia_prima_y_suma_producto(): void
    {
        $mp = MateriaPrima::factory()->create(['unidad_base' => 'kg']);
        InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => 100000, 'costo_promedio' => 5000]);

        $pt = $this->productoConReceta([$mp->id => 10]); // 10 g por unidad

        $this->actingAs($this->user)
            ->post('/inventario/produccion', [
                'producto_terminado_id' => $pt->id,
                'cantidad' => '30',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('inventario.movimientos.index'))
            ->assertSessionHas('success');

        // 30 unidades × 10 g = 300 g consumidos → 100000 - 300 = 99700
        $this->assertSame(99700, $mp->fresh()->inventario->stock_gramos);
        // +30 unidades de producto
        $this->assertSame(30, $pt->fresh()->inventario->disponible);

        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'consumo_produccion',
            'origen_type' => $mp->getMorphClass(),
            'origen_id' => $mp->id,
            'cantidad' => 300,
            'direccion' => 'salida',
        ]);
        $this->assertDatabaseHas('movimientos_inventario', [
            'tipo' => 'producto_producido',
            'origen_type' => $pt->getMorphClass(),
            'origen_id' => $pt->id,
            'cantidad' => 30,
            'direccion' => 'entrada',
        ]);
    }

    #[Test]
    public function rechaza_produccion_sin_receta(): void
    {
        $pt = ProductoTerminado::factory()->create();

        $this->actingAs($this->user)
            ->post('/inventario/produccion', [
                'producto_terminado_id' => $pt->id,
                'cantidad' => '5',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseCount('inventario_producto_terminado', 0);
    }

    #[Test]
    public function rechaza_produccion_con_stock_insuficiente_de_materia_prima(): void
    {
        $mp = MateriaPrima::factory()->create();
        InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => 100, 'costo_promedio' => 0]);
        $pt = $this->productoConReceta([$mp->id => 30]); // necesita 30 g × 50 = 1500 g

        $this->actingAs($this->user)
            ->post('/inventario/produccion', [
                'producto_terminado_id' => $pt->id,
                'cantidad' => '50',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHas('error');

        // Sin cambios: ni MP ni PT ni movimientos
        $this->assertSame(100, $mp->fresh()->inventario->stock_gramos);
        $this->assertDatabaseCount('movimientos_inventario', 0);
        $this->assertDatabaseCount('detalle_receta', 1);
    }

    #[Test]
    public function la_vista_de_produccion_se_renders(): void
    {
        $pt = ProductoTerminado::factory()->create();
        $pt->receta()->create(['materia_prima_id' => MateriaPrima::factory()->create()->id, 'gramos_por_unidad' => 10]);

        $this->actingAs($this->user)
            ->get('/inventario/produccion/crear')
            ->assertOk();
    }
}

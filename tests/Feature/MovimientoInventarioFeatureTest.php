<?php

namespace Tests\Feature;

use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MovimientoInventarioFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function lista_movimientos(): void
    {
        $mp = MateriaPrima::factory()->create();
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

        $this->actingAs($this->user)
            ->get('/inventario/movimientos')
            ->assertOk()
            ->assertSee($mp->nombre);
    }

    #[Test]
    public function registra_una_compra_de_materia_prima(): void
    {
        $mp = MateriaPrima::factory()->create(['unidad_base' => 'kg']);

        $this->actingAs($this->user)
            ->post('/inventario/movimientos', [
                'tipo' => 'compra_recepcion',
                'materia_prima_id' => $mp->id,
                'cantidad' => '5', // 5 kg
                'costo_total' => '100000',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertRedirect(route('inventario.movimientos.index'))
            ->assertSessionHas('success');

        // 5 kg → 5000 gramos
        $this->assertSame(5000, $mp->fresh()->inventario->stock_gramos);
        $this->assertDatabaseCount('movimientos_inventario', 1);
    }

    #[Test]
    public function rechaza_venta_con_stock_insuficiente(): void
    {
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 10,
            'comprometido' => 0,
        ]);

        $this->actingAs($this->user)
            ->post('/inventario/movimientos', [
                'tipo' => 'venta_despacho',
                'producto_terminado_id' => $pt->id,
                'cantidad' => '50',
                'fecha' => now()->format('Y-m-d\TH:i'),
            ])
            ->assertSessionHas('error');

        $this->assertSame(10, $pt->fresh()->inventario->disponible);
        $this->assertDatabaseCount('movimientos_inventario', 0);
    }

    #[Test]
    public function anula_un_movimiento(): void
    {
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 70, // 100 - 30 tras la venta
            'comprometido' => 0,
        ]);

        $mov = $pt->movimientos()->create([
            'tipo' => 'venta_despacho',
            'origen_type' => $pt->getMorphClass(),
            'origen_id' => $pt->id,
            'cantidad' => 30,
            'direccion' => 'salida',
            'saldo' => 70,
            'fecha' => now(),
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->post("/inventario/movimientos/{$mov->id}/anular", [
                'motivo' => 'Error de tipeo',
            ])
            ->assertRedirect(route('inventario.movimientos.show', $mov))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('movimientos_inventario', [
            'id' => $mov->id,
            'tipo' => 'venta_despacho',
        ]);

        $reversion = MovimientoInventario::where('movimiento_original_id', $mov->id)->first();
        $this->assertNotNull($reversion);
        $this->assertSame('anulacion_reversion', $reversion->tipo);
        $this->assertSame(100, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function el_formulario_de_movimiento_requiere_autenticacion(): void
    {
        $this->get('/inventario/movimientos/crear')
            ->assertRedirect(route('login'));
    }
}

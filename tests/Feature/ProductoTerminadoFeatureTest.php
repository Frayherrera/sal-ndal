<?php

namespace Tests\Feature;

use App\Models\ProductoTerminado;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductoTerminadoFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function lista_productos_terminados(): void
    {
        ProductoTerminado::factory()->create(['nombre' => 'Comino 100g']);

        $this->actingAs($this->user)
            ->get('/inventario/productos-terminados')
            ->assertOk()
            ->assertSee('Comino 100g');
    }

    #[Test]
    public function crea_producto_terminado(): void
    {
        $this->actingAs($this->user)
            ->post('/inventario/productos-terminados', [
                'nombre' => 'Orégano',
                'presentaciones' => [
                    ['categoria' => 'ESP', 'peso_neto' => 50, 'precio_venta' => 5000, 'stock_minimo' => 20, 'activo' => 1],
                ],
            ])
            ->assertRedirect();

        $pt = ProductoTerminado::where('nombre', 'Orégano')->firstOrFail();
        $this->assertDatabaseHas('productos', ['id' => $pt->producto_id, 'nombre' => 'Orégano']);
        $this->assertDatabaseHas('inventario_producto_terminado', ['producto_terminado_id' => $pt->id, 'disponible' => 0]);
    }

    #[Test]
    public function actualiza_producto_terminado(): void
    {
        $pt = ProductoTerminado::factory()->create(['precio_venta' => 1000, 'categoria' => 'ESP']);

        $this->actingAs($this->user)
            ->put("/inventario/productos-terminados/{$pt->producto_id}", [
                'nombre' => $pt->nombre,
                'activo' => 1,
                'presentaciones' => [
                    ['id' => $pt->id, 'categoria' => 'ESP', 'peso_neto' => 100, 'precio_venta' => 12000, 'stock_minimo' => 15, 'activo' => 1],
                ],
            ])
            ->assertRedirect(route('inventario.productos-terminados.show', $pt->producto_id));

        $this->assertDatabaseHas('producto_terminados', ['id' => $pt->id, 'precio_venta' => 12000]);
    }

    #[Test]
    public function toggle_activo_producto(): void
    {
        $pt = ProductoTerminado::factory()->create(['activo' => true]);

        $this->actingAs($this->user)
            ->post("/inventario/productos-terminados/{$pt->producto_id}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('productos', ['id' => $pt->producto_id, 'activo' => false]);
        $this->assertDatabaseHas('producto_terminados', ['id' => $pt->id, 'activo' => false]);
    }
}

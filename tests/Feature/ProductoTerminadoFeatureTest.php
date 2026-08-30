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
                'nombre' => 'Orégano 50g',
                'presentacion' => 'bolsa',
                'peso_neto' => 50,
                'precio_venta' => 5000,
                'stock_minimo' => 20,
            ])
            ->assertRedirect(route('inventario.productos-terminados.index'));

        $this->assertDatabaseHas('producto_terminados', ['nombre' => 'Orégano 50g']);
        $this->assertDatabaseHas('inventario_producto_terminado', [
            'producto_terminado_id' => ProductoTerminado::where('nombre', 'Orégano 50g')->first()->id,
            'disponible' => 0,
        ]);
    }

    #[Test]
    public function actualiza_producto_terminado(): void
    {
        $producto = ProductoTerminado::factory()->create(['precio_venta' => 1000]);

        $this->actingAs($this->user)
            ->put("/inventario/productos-terminados/{$producto->id}", [
                'nombre' => $producto->nombre,
                'presentacion' => $producto->presentacion,
                'peso_neto' => 100,
                'precio_venta' => 12000,
                'stock_minimo' => 15,
                'activo' => 1,
            ])
            ->assertRedirect(route('inventario.productos-terminados.index'));

        $this->assertDatabaseHas('producto_terminados', ['id' => $producto->id, 'precio_venta' => 12000]);
    }

    #[Test]
    public function toggle_activo_producto(): void
    {
        $producto = ProductoTerminado::factory()->create(['activo' => true]);

        $this->actingAs($this->user)
            ->post("/inventario/productos-terminados/{$producto->id}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('producto_terminados', ['id' => $producto->id, 'activo' => false]);
    }
}

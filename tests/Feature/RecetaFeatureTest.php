<?php

namespace Tests\Feature;

use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RecetaFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function lista_recetas(): void
    {
        $pt = ProductoTerminado::factory()->create();

        $this->actingAs($this->user)
            ->get('/inventario/recetas')
            ->assertOk()
            ->assertSee($pt->nombre);
    }

    #[Test]
    public function crea_una_receta_con_lineas(): void
    {
        $pt = ProductoTerminado::factory()->create();
        $mp1 = MateriaPrima::factory()->create();
        $mp2 = MateriaPrima::factory()->create();

        $this->actingAs($this->user)
            ->post('/inventario/recetas', [
                'producto_terminado_id' => $pt->id,
                'lineas' => [
                    ['materia_prima_id' => $mp1->id, 'gramos_por_unidad' => '12.5'],
                    ['materia_prima_id' => $mp2->id, 'gramos_por_unidad' => '3'],
                ],
            ])
            ->assertRedirect(route('inventario.recetas.edit', $pt))
            ->assertSessionHas('success');

        $this->assertSame(2, $pt->receta()->count());
        $this->assertDatabaseHas('detalle_receta', [
            'producto_terminado_id' => $pt->id,
            'materia_prima_id' => $mp1->id,
            'gramos_por_unidad' => 12.5,
        ]);
    }

    #[Test]
    public function edita_una_receta_reemplazando_lineas(): void
    {
        $pt = ProductoTerminado::factory()->create();
        $mp1 = MateriaPrima::factory()->create();
        $mp2 = MateriaPrima::factory()->create();
        $mp3 = MateriaPrima::factory()->create();

        $pt->receta()->create(['materia_prima_id' => $mp1->id, 'gramos_por_unidad' => 10]);
        $pt->receta()->create(['materia_prima_id' => $mp2->id, 'gramos_por_unidad' => 5]);

        $this->actingAs($this->user)
            ->put("/inventario/recetas/{$pt->id}", [
                'lineas' => [
                    ['materia_prima_id' => $mp2->id, 'gramos_por_unidad' => '6'],
                    ['materia_prima_id' => $mp3->id, 'gramos_por_unidad' => '1.5'],
                ],
            ])
            ->assertRedirect(route('inventario.recetas.edit', $pt))
            ->assertSessionHas('success');

        $this->assertSame(2, $pt->receta()->count());
        $this->assertDatabaseMissing('detalle_receta', ['materia_prima_id' => $mp1->id]);
        $this->assertDatabaseHas('detalle_receta', ['materia_prima_id' => $mp3->id]);
    }

    #[Test]
    public function rechaza_linea_duplicada_de_materia_prima(): void
    {
        $pt = ProductoTerminado::factory()->create();
        $mp = MateriaPrima::factory()->create();

        $this->actingAs($this->user)
            ->post('/inventario/recetas', [
                'producto_terminado_id' => $pt->id,
                'lineas' => [
                    ['materia_prima_id' => $mp->id, 'gramos_por_unidad' => '10'],
                    ['materia_prima_id' => $mp->id, 'gramos_por_unidad' => '5'],
                ],
            ])
            ->assertSessionHas('error');

        $this->assertSame(0, $pt->receta()->count());
    }

    #[Test]
    public function elimina_una_receta(): void
    {
        $pt = ProductoTerminado::factory()->create();
        $mp = MateriaPrima::factory()->create();
        $pt->receta()->create(['materia_prima_id' => $mp->id, 'gramos_por_unidad' => 10]);

        $this->actingAs($this->user)
            ->delete("/inventario/recetas/{$pt->id}")
            ->assertRedirect(route('inventario.recetas.index'))
            ->assertSessionHas('success');

        $this->assertSame(0, $pt->receta()->count());
    }
}

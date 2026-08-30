<?php

namespace Tests\Feature;

use App\Models\MateriaPrima;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class MateriaPrimaFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function la_ruta_de_listado_requiere_autenticacion(): void
    {
        $this->get('/inventario/materia-prima')
            ->assertRedirect(route('login'));
    }

    #[Test]
    public function lista_materias_primas(): void
    {
        MateriaPrima::factory()->create(['nombre' => 'Orégano']);

        $this->actingAs($this->user)
            ->get('/inventario/materia-prima')
            ->assertOk()
            ->assertSee('Orégano');
    }

    #[Test]
    public function crea_materia_prima_con_codigo_autogenerado(): void
    {
        $response = $this->actingAs($this->user)
            ->post('/inventario/materia-prima', [
                'nombre' => 'Comino',
                'unidad_base' => 'kg',
                'stock_minimo' => 5,
            ]);

        $response->assertRedirect(route('inventario.materia-prima.index'));

        $this->assertDatabaseHas('materias_primas', ['nombre' => 'Comino']);
        $this->assertDatabaseHas('inventario_materia_prima', [
            'materia_prima_id' => MateriaPrima::where('nombre', 'Comino')->first()->id,
            'stock_gramos' => 0,
        ]);
    }

    #[Test]
    public function no_crea_materia_prima_sin_nombre(): void
    {
        $this->actingAs($this->user)
            ->post('/inventario/materia-prima', ['unidad_base' => 'kg'])
            ->assertSessionHasErrors('nombre');
    }

    #[Test]
    public function no_duplica_codigos(): void
    {
        MateriaPrima::factory()->create(['codigo' => 'MP-1234']);

        $this->actingAs($this->user)
            ->post('/inventario/materia-prima', [
                'nombre' => 'Achiote',
                'codigo' => 'MP-1234',
                'unidad_base' => 'kg',
            ])
            ->assertSessionHasErrors('codigo');
    }

    #[Test]
    public function actualiza_materia_prima(): void
    {
        $materia = MateriaPrima::factory()->create(['nombre' => 'Original']);

        $this->actingAs($this->user)
            ->put("/inventario/materia-prima/{$materia->id}", [
                'nombre' => 'Actualizado',
                'unidad_base' => $materia->unidad_base,
                'stock_minimo' => 10,
                'activo' => 1,
            ])
            ->assertRedirect(route('inventario.materia-prima.index'));

        $this->assertDatabaseHas('materias_primas', ['id' => $materia->id, 'nombre' => 'Actualizado']);
    }

    #[Test]
    public function no_elimina_materia_prima_con_movimientos(): void
    {
        $materia = MateriaPrima::factory()->create();
        $materia->movimientos()->create([
            'tipo' => 'compra_recepcion',
            'origen_type' => $materia->getMorphClass(),
            'origen_id' => $materia->id,
            'cantidad' => 100,
            'direccion' => 'entrada',
            'saldo' => 100,
            'fecha' => now(),
        ]);

        $this->actingAs($this->user)
            ->delete("/inventario/materia-prima/{$materia->id}")
            ->assertRedirect(route('inventario.materia-prima.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('materias_primas', ['id' => $materia->id]);
    }

    #[Test]
    public function toggle_estado_activo(): void
    {
        $materia = MateriaPrima::factory()->create(['activo' => true]);

        $this->actingAs($this->user)
            ->post("/inventario/materia-prima/{$materia->id}/toggle")
            ->assertRedirect();

        $this->assertDatabaseHas('materias_primas', ['id' => $materia->id, 'activo' => false]);
    }
}

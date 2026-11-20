<?php

namespace Tests\Feature;

use App\Models\ConteoFisico;
use App\Models\InventarioMateriaPrima;
use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Prueba el conteo físico a través del formulario, enviando los campos con
 * los mismos nombres que genera la vista (detalles[i][campo]).
 */
class ConteoFisicoFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function conteo(string $tipo): ConteoFisico
    {
        $this->actingAs($this->user)->post('/inventario/conteo-fisico', [
            'tipo' => $tipo,
            'fecha_conteo' => now()->toDateString(),
        ]);

        return ConteoFisico::latest('id')->firstOrFail();
    }

    private function materia(int $gramos, string $unidad = 'kg', string $nombre = 'Comino'): MateriaPrima
    {
        $mp = MateriaPrima::factory()->create(['unidad_base' => $unidad, 'nombre' => $nombre, 'activo' => true]);
        InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => $gramos, 'costo_promedio' => 0]);

        return $mp;
    }

    #[Test]
    public function flujo_completo_de_materia_prima_en_kg_genera_los_ajustes(): void
    {
        $comino = $this->materia(10000, 'kg', 'Comino');   // 10 kg en sistema
        $pimienta = $this->materia(5000, 'kg', 'Pimienta'); // 5 kg en sistema
        $canela = $this->materia(800, 'g', 'Canela');       // 800 g en sistema

        $conteo = $this->conteo('materia_prima');

        $this->actingAs($this->user)
            ->post("/inventario/conteo-fisico/{$conteo->id}/detalles", [
                'detalles' => [
                    0 => ['materia_prima_id' => $comino->id, 'cantidad_fisica' => '9.75', 'motivo' => 'Merma'],
                    1 => ['materia_prima_id' => $pimienta->id, 'cantidad_fisica' => '5.2', 'motivo' => 'Sobrante'],
                    2 => ['materia_prima_id' => $canela->id, 'cantidad_fisica' => '800', 'motivo' => ''],
                    3 => ['materia_prima_id' => '', 'cantidad_fisica' => '', 'motivo' => ''], // fila vacía: se ignora
                ],
            ])
            ->assertSessionHas('success');

        $detalles = $conteo->detalles()->get()->keyBy('materia_prima_id');

        $this->assertCount(3, $detalles);
        $this->assertSame(10000, $detalles[$comino->id]->stock_sistema);
        $this->assertSame(9750, $detalles[$comino->id]->cantidad_fisica);
        $this->assertSame(-250, $detalles[$comino->id]->diferencia);
        $this->assertSame('Merma', $detalles[$comino->id]->motivo);
        $this->assertSame(200, $detalles[$pimienta->id]->diferencia);
        $this->assertSame(0, $detalles[$canela->id]->diferencia);

        $this->actingAs($this->user)->post("/inventario/conteo-fisico/{$conteo->id}/completar")->assertSessionHas('success');
        $this->actingAs($this->user)->post("/inventario/conteo-fisico/{$conteo->id}/aprobar")->assertSessionHas('success');

        $this->assertSame('aprobado', $conteo->fresh()->estado);
        $this->assertSame(9750, $comino->fresh()->inventario->stock_gramos);
        $this->assertSame(5200, $pimienta->fresh()->inventario->stock_gramos);
        $this->assertSame(800, $canela->fresh()->inventario->stock_gramos);

        // Solo las dos líneas con diferencia generan movimiento
        $movimientos = MovimientoInventario::where('conteo_fisico_id', $conteo->id)->get();
        $this->assertCount(2, $movimientos);
        $this->assertEqualsCanonicalizing(['ajuste_negativo', 'ajuste_positivo'], $movimientos->pluck('tipo')->all());
    }

    #[Test]
    public function conteo_de_producto_terminado_en_unidades(): void
    {
        $pt = ProductoTerminado::factory()->create(['activo' => true]);
        InventarioProductoTerminado::create(['producto_terminado_id' => $pt->id, 'disponible' => 40, 'comprometido' => 0]);

        $conteo = $this->conteo('producto_terminado');

        $this->actingAs($this->user)->post("/inventario/conteo-fisico/{$conteo->id}/detalles", [
            'detalles' => [['producto_terminado_id' => $pt->id, 'cantidad_fisica' => '37']],
        ])->assertSessionHas('success');

        $this->actingAs($this->user)->post("/inventario/conteo-fisico/{$conteo->id}/completar");
        $this->actingAs($this->user)->post("/inventario/conteo-fisico/{$conteo->id}/aprobar");

        $this->assertSame(37, $pt->fresh()->inventario->disponible);
    }

    #[Test]
    public function guardar_sin_lineas_muestra_un_mensaje_y_no_un_error(): void
    {
        $conteo = $this->conteo('materia_prima');

        $this->actingAs($this->user)
            ->from("/inventario/conteo-fisico/{$conteo->id}")
            ->post("/inventario/conteo-fisico/{$conteo->id}/detalles", [])
            ->assertRedirect("/inventario/conteo-fisico/{$conteo->id}")
            ->assertSessionHas('error');

        $this->actingAs($this->user)
            ->post("/inventario/conteo-fisico/{$conteo->id}/detalles", [
                'detalles' => [['materia_prima_id' => '', 'cantidad_fisica' => '']],
            ])
            ->assertSessionHas('error');
    }

    #[Test]
    public function un_conteo_completado_no_acepta_cambios_de_lineas(): void
    {
        $mp = $this->materia(1000);
        $conteo = $this->conteo('materia_prima');

        $this->actingAs($this->user)->post("/inventario/conteo-fisico/{$conteo->id}/detalles", [
            'detalles' => [['materia_prima_id' => $mp->id, 'cantidad_fisica' => '1']],
        ]);
        $this->actingAs($this->user)->post("/inventario/conteo-fisico/{$conteo->id}/completar");

        $this->actingAs($this->user)
            ->post("/inventario/conteo-fisico/{$conteo->id}/detalles", [
                'detalles' => [['materia_prima_id' => $mp->id, 'cantidad_fisica' => '3']],
            ])
            ->assertSessionHas('error');

        $this->assertSame(1000, $conteo->detalles()->first()->cantidad_fisica);

        // La vista de un conteo completado ya no muestra el formulario de captura
        $this->actingAs($this->user)
            ->get("/inventario/conteo-fisico/{$conteo->id}")
            ->assertOk()
            ->assertDontSee('Guardar líneas')
            ->assertSee('Aprobar y Generar Ajustes');
    }

    #[Test]
    public function la_vista_genera_campos_con_indice_por_fila(): void
    {
        $this->materia(2500, 'kg', 'Orégano');
        $conteo = $this->conteo('materia_prima');

        $this->actingAs($this->user)
            ->get("/inventario/conteo-fisico/{$conteo->id}")
            ->assertOk()
            ->assertSee("'detalles[' + i + '][' + el.dataset.name + ']'", false)
            ->assertDontSee('detalles[][', false)
            ->assertSee('Orégano');
    }
}

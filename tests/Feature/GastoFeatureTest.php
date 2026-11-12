<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\MateriaPrima;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GastoFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function gasto(array $atributos = []): Gasto
    {
        $gasto = Gasto::factory()->create(array_merge(['user_id' => $this->user->id], $atributos));
        $gasto->forceFill(['numero' => 'GTO-'.str_pad((string) $gasto->id, 5, '0', STR_PAD_LEFT)])->save();

        return $gasto;
    }

    #[Test]
    public function las_rutas_de_gastos_requieren_autenticacion(): void
    {
        $this->get('/gastos')->assertRedirect(route('login'));
        $this->get('/gastos/crear')->assertRedirect(route('login'));
        $this->get('/gastos/categorias')->assertRedirect(route('login'));
        $this->get('/gastos/reportes/resultado')->assertRedirect(route('login'));
    }

    #[Test]
    public function lista_los_gastos_y_su_total(): void
    {
        $this->gasto(['descripcion' => 'Pago de arriendo', 'monto' => 900000]);
        $this->gasto(['descripcion' => 'Gasto anulado', 'monto' => 50000, 'estado' => Gasto::ESTADO_ANULADO]);

        $this->actingAs($this->user)
            ->get('/gastos')
            ->assertOk()
            ->assertSee('Pago de arriendo')
            ->assertSee('Gasto anulado')
            ->assertSee('$900.000');
    }

    #[Test]
    public function filtra_por_categoria_y_texto(): void
    {
        $luz = CategoriaGasto::factory()->create(['nombre' => 'Luz']);
        $this->gasto(['descripcion' => 'Recibo de luz', 'categoria_gasto_id' => $luz->id]);
        $this->gasto(['descripcion' => 'Gasolina moto']);

        $this->actingAs($this->user)
            ->get('/gastos?categoria_id='.$luz->id)
            ->assertOk()
            ->assertSee('Recibo de luz')
            ->assertDontSee('Gasolina moto');

        $this->actingAs($this->user)
            ->get('/gastos?q=gasolina')
            ->assertOk()
            ->assertSee('Gasolina moto')
            ->assertDontSee('Recibo de luz');
    }

    #[Test]
    public function el_formulario_no_ofrece_la_categoria_automatica(): void
    {
        $this->actingAs($this->user)
            ->get('/gastos/crear')
            ->assertOk()
            ->assertSee('Arriendo')
            ->assertDontSee('<option value="'.CategoriaGasto::compraMateriaPrima()->id.'"', false);
    }

    #[Test]
    public function registra_un_gasto(): void
    {
        $categoria = CategoriaGasto::where('nombre', 'Arriendo')->firstOrFail();

        $this->actingAs($this->user)
            ->post('/gastos', [
                'fecha' => now()->toDateString(),
                'categoria_gasto_id' => $categoria->id,
                'descripcion' => 'Arriendo de noviembre',
                'monto' => 1200000,
                'metodo_pago' => 'transferencia',
                'proveedor' => 'Inmobiliaria Centro',
            ])
            ->assertRedirect(route('gastos.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('gastos', [
            'descripcion' => 'Arriendo de noviembre',
            'categoria_gasto_id' => $categoria->id,
            'monto' => 1200000,
            'metodo_pago' => 'transferencia',
            'proveedor' => 'Inmobiliaria Centro',
            'user_id' => $this->user->id,
            'numero' => 'GTO-00001',
        ]);
    }

    #[Test]
    public function valida_los_campos_obligatorios(): void
    {
        $this->actingAs($this->user)
            ->post('/gastos', ['monto' => 0, 'fecha' => now()->addDay()->toDateString()])
            ->assertSessionHasErrors(['categoria_gasto_id', 'descripcion', 'monto', 'fecha']);

        $this->assertDatabaseCount('gastos', 0);
    }

    #[Test]
    public function edita_un_gasto_manual(): void
    {
        $gasto = $this->gasto(['descripcion' => 'Original']);

        $this->actingAs($this->user)->get("/gastos/{$gasto->id}/editar")->assertOk()->assertSee('Original');

        $this->actingAs($this->user)
            ->put("/gastos/{$gasto->id}", [
                'fecha' => now()->toDateString(),
                'categoria_gasto_id' => $gasto->categoria_gasto_id,
                'descripcion' => 'Corregido',
                'monto' => 15000,
            ])
            ->assertRedirect(route('gastos.index'));

        $this->assertSame('Corregido', $gasto->fresh()->descripcion);
        $this->assertSame(15000.0, (float) $gasto->fresh()->monto);
    }

    #[Test]
    public function anula_un_gasto_manual(): void
    {
        $gasto = $this->gasto();

        $this->actingAs($this->user)->get("/gastos/{$gasto->id}/anular")->assertOk();

        $this->actingAs($this->user)
            ->post("/gastos/{$gasto->id}/anular", ['motivo' => 'Duplicado'])
            ->assertRedirect(route('gastos.index'));

        $this->assertSame(Gasto::ESTADO_ANULADO, $gasto->fresh()->estado);
    }

    #[Test]
    public function registrar_una_compra_con_costo_crea_el_gasto_y_el_costo_es_obligatorio(): void
    {
        $mp = MateriaPrima::factory()->create(['unidad_base' => 'kg', 'nombre' => 'Orégano']);

        // Sin costo: la compra se rechaza
        $this->actingAs($this->user)
            ->post('/inventario/movimientos', [
                'tipo' => 'compra_recepcion',
                'materia_prima_id' => $mp->id,
                'cantidad' => '2',
            ])
            ->assertSessionHasErrors('costo_total');

        $this->assertDatabaseCount('movimientos_inventario', 0);

        // Con costo: se crea la compra y su gasto
        $this->actingAs($this->user)
            ->post('/inventario/movimientos', [
                'tipo' => 'compra_recepcion',
                'materia_prima_id' => $mp->id,
                'cantidad' => '2',
                'costo_total' => '64000',
            ])
            ->assertSessionHas('success');

        $gasto = Gasto::firstOrFail();

        $this->assertTrue($gasto->esAutomatico());
        $this->assertSame(64000.0, (float) $gasto->monto);
        $this->assertStringContainsString('Orégano', $gasto->descripcion);

        // El gasto automático no se edita desde Gastos
        $this->actingAs($this->user)
            ->get("/gastos/{$gasto->id}/editar")
            ->assertRedirect(route('gastos.index'))
            ->assertSessionHas('error');

        $this->actingAs($this->user)
            ->get("/gastos/{$gasto->id}/anular")
            ->assertOk()
            ->assertSee('Gasto generado por una compra');

        // El detalle del movimiento enlaza el gasto
        $this->actingAs($this->user)
            ->get("/inventario/movimientos/{$gasto->movimiento_inventario_id}")
            ->assertOk()
            ->assertSee($gasto->numero);
    }

    #[Test]
    public function exporta_los_gastos_en_csv(): void
    {
        $gasto = $this->gasto(['descripcion' => 'Gasto para CSV']);

        $response = $this->actingAs($this->user)->get('/gastos/exportar');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $contenido = $response->streamedContent();
        $this->assertStringContainsString('Número;Fecha;Categoría', $contenido);
        $this->assertStringContainsString($gasto->numero, $contenido);
        $this->assertStringContainsString('Gasto para CSV', $contenido);
    }

    #[Test]
    public function reporte_por_categoria_suma_solo_gastos_registrados_del_periodo(): void
    {
        $luz = CategoriaGasto::factory()->create(['nombre' => 'Energía Test']);
        $viejo = CategoriaGasto::factory()->create(['nombre' => 'Categoría Vieja']);

        $this->gasto(['categoria_gasto_id' => $luz->id, 'monto' => 30000]);
        $this->gasto(['categoria_gasto_id' => $luz->id, 'monto' => 20000]);
        $this->gasto(['categoria_gasto_id' => $luz->id, 'monto' => 99000, 'estado' => Gasto::ESTADO_ANULADO]);
        $this->gasto(['categoria_gasto_id' => $viejo->id, 'fecha' => now()->subYear()->toDateString()]);

        $this->actingAs($this->user)
            ->get('/gastos/reportes/categorias?desde='.now()->startOfMonth()->toDateString().'&hasta='.now()->toDateString())
            ->assertOk()
            ->assertSee('Energía Test')
            ->assertSee('$50.000')
            ->assertDontSee('Categoría Vieja');
    }

    #[Test]
    public function reporte_de_ventas_menos_gastos_por_mes(): void
    {
        $cliente = Cliente::factory()->create();

        Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'fecha' => now(),
            'subtotal' => 500000,
            'total' => 500000,
        ]);
        Venta::factory()->create([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'fecha' => now(),
            'subtotal' => 70000,
            'total' => 70000,
            'estado' => Venta::ESTADO_ANULADA,
        ]);

        $this->gasto(['monto' => 180000]);

        $this->actingAs($this->user)
            ->get('/gastos/reportes/resultado?anio='.now()->year)
            ->assertOk()
            ->assertSee('$500.000')
            ->assertSee('$180.000')
            ->assertSee('$320.000')
            ->assertDontSee('$570.000');
    }

    #[Test]
    public function el_dashboard_muestra_los_gastos_del_mes(): void
    {
        $this->gasto(['monto' => 45000]);

        $this->actingAs($this->user)
            ->get('/gestion')
            ->assertOk()
            ->assertSee('Gastado este mes')
            ->assertSee('$45.000');
    }

    #[Test]
    public function crud_de_categorias(): void
    {
        $this->actingAs($this->user)->get('/gastos/categorias')->assertOk()->assertSee('Compra de materia prima');
        $this->actingAs($this->user)->get('/gastos/categorias/create')->assertOk();

        $this->actingAs($this->user)
            ->post('/gastos/categorias', ['nombre' => 'Papelería', 'activo' => '1'])
            ->assertRedirect(route('gastos.categorias.index'));

        $categoria = CategoriaGasto::where('nombre', 'Papelería')->firstOrFail();
        $this->assertTrue($categoria->activo);

        $this->actingAs($this->user)
            ->post('/gastos/categorias', ['nombre' => 'Papelería'])
            ->assertSessionHasErrors('nombre');

        $this->actingAs($this->user)->get("/gastos/categorias/{$categoria->id}/edit")->assertOk();

        $this->actingAs($this->user)
            ->put("/gastos/categorias/{$categoria->id}", ['nombre' => 'Papelería y oficina', 'activo' => '0'])
            ->assertRedirect(route('gastos.categorias.index'));

        $this->assertFalse($categoria->fresh()->activo);
        $this->assertSame('Papelería y oficina', $categoria->fresh()->nombre);

        $this->actingAs($this->user)
            ->delete("/gastos/categorias/{$categoria->id}")
            ->assertRedirect(route('gastos.categorias.index'));

        $this->assertDatabaseMissing('categorias_gasto', ['id' => $categoria->id]);
    }

    #[Test]
    public function no_elimina_una_categoria_con_gastos(): void
    {
        $gasto = $this->gasto();

        $this->actingAs($this->user)
            ->from('/gastos/categorias')
            ->delete("/gastos/categorias/{$gasto->categoria_gasto_id}")
            ->assertRedirect('/gastos/categorias')
            ->assertSessionHas('error');

        $this->assertDatabaseHas('categorias_gasto', ['id' => $gasto->categoria_gasto_id]);
    }
}

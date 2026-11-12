<?php

namespace Tests\Unit;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\MateriaPrima;
use App\Models\User;
use App\Services\CategoriaGastoService;
use App\Services\GastoService;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class GastoServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private GastoService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->service = app(GastoService::class);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function crearGasto(array $extra = []): Gasto
    {
        return $this->service->crear(array_merge([
            'fecha' => now()->toDateString(),
            'categoria_gasto_id' => CategoriaGasto::factory()->create()->id,
            'descripcion' => 'Factura de luz',
            'monto' => 85000,
            'metodo_pago' => 'transferencia',
            'proveedor' => 'Empresa de energía',
        ], $extra));
    }

    private function comprar(MateriaPrima $mp, ?float $costo, int $gramos = 5000)
    {
        return app(MovimientoInventarioService::class)->registrar([
            'tipo' => 'compra_recepcion',
            'origen' => $mp,
            'cantidad' => $gramos,
            'costo_total' => $costo,
            'documento' => 'FAC-77',
        ]);
    }

    #[Test]
    public function la_migracion_crea_las_categorias_iniciales(): void
    {
        $this->assertDatabaseHas('categorias_gasto', ['nombre' => 'Arriendo']);
        $this->assertDatabaseHas('categorias_gasto', ['clave' => CategoriaGasto::CLAVE_COMPRA_MATERIA_PRIMA]);
    }

    #[Test]
    public function crea_un_gasto_con_consecutivo(): void
    {
        $gasto = $this->crearGasto();

        $this->assertSame('GTO-00001', $gasto->numero);
        $this->assertSame(85000.0, (float) $gasto->monto);
        $this->assertSame('transferencia', $gasto->metodo_pago);
        $this->assertSame('Empresa de energía', $gasto->proveedor);
        $this->assertSame(Gasto::ESTADO_REGISTRADO, $gasto->estado);
        $this->assertFalse($gasto->esAutomatico());
    }

    #[Test]
    public function rechaza_monto_cero(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/mayor a cero/');

        $this->crearGasto(['monto' => 0]);
    }

    #[Test]
    public function rechaza_categorias_inactivas(): void
    {
        $categoria = CategoriaGasto::factory()->create(['activo' => false, 'nombre' => 'Vieja']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/inactiva/');

        $this->crearGasto(['categoria_gasto_id' => $categoria->id]);
    }

    #[Test]
    public function actualiza_un_gasto_manual(): void
    {
        $gasto = $this->crearGasto();

        $actualizado = $this->service->actualizar($gasto, [
            'fecha' => now()->toDateString(),
            'categoria_gasto_id' => $gasto->categoria_gasto_id,
            'descripcion' => 'Factura de luz corregida',
            'monto' => 90000,
            'metodo_pago' => '',
            'proveedor' => '',
        ]);

        $this->assertSame('Factura de luz corregida', $actualizado->descripcion);
        $this->assertSame(90000.0, (float) $actualizado->monto);
        $this->assertNull($actualizado->metodo_pago);
        $this->assertNull($actualizado->proveedor);
    }

    #[Test]
    public function anula_un_gasto_manual_sin_borrarlo(): void
    {
        $gasto = $this->crearGasto();

        $anulado = $this->service->anular($gasto, 'Registrado dos veces');

        $this->assertSame(Gasto::ESTADO_ANULADO, $anulado->estado);
        $this->assertSame('Registrado dos veces', $anulado->motivo_anulacion);
        $this->assertNotNull($anulado->anulado_at);
        $this->assertDatabaseCount('gastos', 1);
    }

    #[Test]
    public function no_anula_dos_veces(): void
    {
        $gasto = $this->crearGasto();
        $this->service->anular($gasto, 'Primera');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ya está anulado/');

        $this->service->anular($gasto->fresh(), 'Segunda');
    }

    #[Test]
    public function una_compra_con_costo_genera_su_gasto_automatico(): void
    {
        $mp = MateriaPrima::factory()->create(['nombre' => 'Comino', 'proveedor' => 'Especias del Valle']);

        $movimiento = $this->comprar($mp, 120000, 5500);

        $gasto = Gasto::where('movimiento_inventario_id', $movimiento->id)->first();

        $this->assertNotNull($gasto);
        $this->assertTrue($gasto->esAutomatico());
        $this->assertSame(120000.0, (float) $gasto->monto);
        $this->assertSame(CategoriaGasto::CLAVE_COMPRA_MATERIA_PRIMA, $gasto->categoria->clave);
        $this->assertSame('Especias del Valle', $gasto->proveedor);
        $this->assertStringContainsString('Comino', $gasto->descripcion);
        $this->assertStringContainsString('5,5 kg', $gasto->descripcion);
        $this->assertStringContainsString('FAC-77', $gasto->descripcion);
        $this->assertMatchesRegularExpression('/^GTO-\d{5}$/', $gasto->numero);
    }

    #[Test]
    public function una_compra_sin_costo_no_genera_gasto(): void
    {
        $mp = MateriaPrima::factory()->create();

        $this->comprar($mp, null);
        $this->comprar($mp, 0);

        $this->assertDatabaseCount('gastos', 0);
    }

    #[Test]
    public function otros_movimientos_no_generan_gasto(): void
    {
        $mp = MateriaPrima::factory()->create();
        $this->comprar($mp, null, 10000);

        app(MovimientoInventarioService::class)->registrar([
            'tipo' => 'ajuste_positivo',
            'origen' => $mp,
            'cantidad' => 100,
            'costo_total' => 5000,
            'motivo' => 'Sobrante',
        ]);

        $this->assertDatabaseCount('gastos', 0);
    }

    #[Test]
    public function anular_la_compra_anula_su_gasto(): void
    {
        $mp = MateriaPrima::factory()->create();
        $movimiento = $this->comprar($mp, 50000);

        app(MovimientoInventarioService::class)->anular($movimiento, 'Compra mal digitada');

        $gasto = Gasto::where('movimiento_inventario_id', $movimiento->id)->first();

        $this->assertSame(Gasto::ESTADO_ANULADO, $gasto->estado);
        $this->assertSame('Compra mal digitada', $gasto->motivo_anulacion);
    }

    #[Test]
    public function el_gasto_automatico_no_se_edita_ni_se_anula_directamente(): void
    {
        $mp = MateriaPrima::factory()->create();
        $movimiento = $this->comprar($mp, 50000);
        $gasto = Gasto::where('movimiento_inventario_id', $movimiento->id)->first();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/compra de inventario/');

        $this->service->anular($gasto, 'Intento');
    }

    #[Test]
    public function no_elimina_categorias_con_gastos_ni_del_sistema(): void
    {
        $servicio = app(CategoriaGastoService::class);
        $gasto = $this->crearGasto();

        try {
            $servicio->eliminar($gasto->categoria);
            $this->fail('Debió rechazar la eliminación de una categoría con gastos.');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('Desactívala', $e->getMessage());
        }

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/sistema/');

        $servicio->eliminar(CategoriaGasto::compraMateriaPrima());
    }

    #[Test]
    public function no_desactiva_la_categoria_del_sistema(): void
    {
        $categoria = CategoriaGasto::compraMateriaPrima();

        $this->expectException(RuntimeException::class);

        app(CategoriaGastoService::class)->actualizar($categoria, [
            'nombre' => $categoria->nombre,
            'activo' => false,
        ]);
    }
}

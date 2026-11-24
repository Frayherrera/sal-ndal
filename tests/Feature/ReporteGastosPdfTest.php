<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\MateriaPrima;
use App\Models\User;
use App\Services\MovimientoInventarioService;
use App\Services\ReporteGastosService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReporteGastosPdfTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    private function gasto(CategoriaGasto $categoria, float $monto, string $fecha, array $extra = []): Gasto
    {
        return Gasto::factory()->create(array_merge([
            'categoria_gasto_id' => $categoria->id,
            'monto' => $monto,
            'fecha' => $fecha,
            'user_id' => $this->user->id,
        ], $extra));
    }

    private function generar(array $filtros = []): array
    {
        $consulta = Gasto::query()
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('fecha', '<=', $h))
            ->when($filtros['categoria_id'] ?? null, fn ($q, $c) => $q->where('categoria_gasto_id', $c));

        return app(ReporteGastosService::class)->generar($consulta, $filtros);
    }

    #[Test]
    public function calcula_indicadores_y_separa_compras_de_materia_prima(): void
    {
        $ruta = CategoriaGasto::factory()->create(['nombre' => 'Ruta']);
        $this->gasto($ruta, 70000, '2026-11-03', ['descripcion' => 'Comida']);
        $this->gasto($ruta, 129727, '2026-11-05', ['descripcion' => 'A.C.P.M.']);
        $this->gasto($ruta, 999000, '2026-11-05', ['estado' => Gasto::ESTADO_ANULADO]);

        // Compra con costo: genera un gasto automático
        app(MovimientoInventarioService::class)->registrar([
            'tipo' => 'compra_recepcion',
            'origen' => MateriaPrima::factory()->create(),
            'cantidad' => 5000,
            'costo_total' => 200000,
            'fecha' => '2026-11-04 10:00:00',
        ]);

        $k = $this->generar(['desde' => '2026-11-01', 'hasta' => '2026-11-07'])['kpis'];

        $this->assertSame(3, $k['count']);
        $this->assertSame(399727.0, $k['total']);
        $this->assertSame(200000.0, $k['compras_total']);
        $this->assertSame(1, $k['compras_count']);
        $this->assertSame(199727.0, $k['operativos_total']);
        $this->assertSame(2, $k['operativos_count']);
        $this->assertSame(200000.0, $k['mayor_monto']);
        $this->assertSame(1, $k['anulados_count']);
        $this->assertSame(2, $k['categorias_count']);
    }

    #[Test]
    public function agrupa_por_categoria_por_dia_y_por_proveedor(): void
    {
        $ruta = CategoriaGasto::factory()->create(['nombre' => 'Ruta']);
        $santini = CategoriaGasto::factory()->create(['nombre' => 'Santini']);

        $this->gasto($santini, 500000, '2026-11-08', ['proveedor' => 'Marcela']);
        $this->gasto($santini, 500000, '2026-11-08', ['proveedor' => 'Yorgelis']);
        $this->gasto($ruta, 70000, '2026-11-10', ['proveedor' => 'marcela ']);

        $datos = $this->generar(['desde' => '2026-11-08', 'hasta' => '2026-11-10']);

        $this->assertSame('Santini', $datos['por_categoria'][0]['categoria']);
        $this->assertSame(93.5, $datos['por_categoria'][0]['participacion']);
        $this->assertSame(2, $datos['por_categoria'][0]['count']);

        $this->assertSame('dia', $datos['por_periodo']['agrupacion']);
        $this->assertSame([1000000.0, 0.0, 70000.0], array_column($datos['por_periodo']['filas'], 'total'));

        // Proveedores agrupados sin distinguir mayúsculas ni espacios
        $this->assertSame('Marcela', $datos['top_proveedores'][0]['proveedor']);
        $this->assertSame(570000.0, $datos['top_proveedores'][0]['total']);
        $this->assertSame(2, $datos['top_proveedores'][0]['count']);
    }

    #[Test]
    public function respeta_el_filtro_de_categoria(): void
    {
        $ruta = CategoriaGasto::factory()->create(['nombre' => 'Ruta']);
        $otra = CategoriaGasto::factory()->create();
        $this->gasto($ruta, 70000, '2026-11-03');
        $this->gasto($otra, 99000, '2026-11-04');

        $datos = $this->generar(['categoria_id' => $ruta->id]);

        $this->assertSame(70000.0, $datos['kpis']['total']);
        $this->assertCount(1, $datos['detalle']);
        $this->assertSame('2026-11-03', $datos['desde']->toDateString());
    }

    #[Test]
    public function el_pdf_de_gastos_requiere_autenticacion(): void
    {
        auth()->logout();

        $this->get('/gastos/exportar-pdf')->assertRedirect(route('login'));
    }

    #[Test]
    public function descarga_el_pdf_de_gastos_con_los_filtros(): void
    {
        $ruta = CategoriaGasto::factory()->create(['nombre' => 'Ruta']);
        $this->gasto($ruta, 70000, now()->toDateString(), ['descripcion' => 'Almuerzo', 'proveedor' => 'Restaurante']);

        $desde = now()->startOfMonth()->toDateString();
        $hasta = now()->toDateString();

        $respuesta = $this->get("/gastos/exportar-pdf?desde={$desde}&hasta={$hasta}&categoria_id={$ruta->id}");

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        $this->assertStringContainsString("reporte-gastos-{$desde}_a_{$hasta}.pdf", $respuesta->headers->get('content-disposition'));

        // Sin gastos también genera el PDF
        $vacio = $this->get('/gastos/exportar-pdf?desde=2020-01-01&hasta=2020-01-31');
        $vacio->assertOk();
        $this->assertStringStartsWith('%PDF', $vacio->getContent());
    }

    #[Test]
    public function el_listado_de_gastos_muestra_el_boton_pdf(): void
    {
        $this->get('/gastos?desde=2026-11-01')
            ->assertOk()
            ->assertSee(route('gastos.export-pdf', ['desde' => '2026-11-01']), false);
    }
}

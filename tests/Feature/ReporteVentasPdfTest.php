<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use App\Services\ReporteVentasService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReporteVentasPdfTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['name' => 'Fray']);
    }

    private function venta(Cliente $cliente, float $total, string $fecha, array $extra = []): Venta
    {
        $venta = Venta::factory()->create(array_merge([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'fecha' => $fecha,
            'subtotal' => $total,
            'total' => $total,
            'forma_pago' => Venta::FORMA_CREDITO,
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ], $extra));
        $venta->forceFill(['numero' => 'VTA-'.str_pad((string) $venta->id, 5, '0', STR_PAD_LEFT)])->save();

        return $venta;
    }

    private function pagar(Venta $venta, float $monto): void
    {
        $venta->pagos()->create(['fecha' => $venta->fecha, 'monto' => $monto, 'metodo' => 'efectivo']);
    }

    private function generar(array $filtros = []): array
    {
        $consulta = Venta::query()
            ->when($filtros['desde'] ?? null, fn ($q, $d) => $q->whereDate('fecha', '>=', $d))
            ->when($filtros['hasta'] ?? null, fn ($q, $h) => $q->whereDate('fecha', '<=', $h))
            ->when($filtros['cliente_id'] ?? null, fn ($q, $c) => $q->where('cliente_id', $c));

        return app(ReporteVentasService::class)->generar($consulta, $filtros);
    }

    #[Test]
    public function calcula_los_indicadores_solo_con_ventas_confirmadas(): void
    {
        $jesus = Cliente::factory()->create(['nombre' => 'JESUS DUQUE']);
        $said = Cliente::factory()->create(['nombre' => 'SAID RUEDA']);

        $contado = $this->venta($jesus, 230000, '2026-11-03', ['forma_pago' => Venta::FORMA_CONTADO]);
        $this->pagar($contado, 230000);
        $credito = $this->venta($said, 100000, '2026-11-04');
        $this->pagar($credito, 40000);
        $this->venta($said, 70000, '2026-11-04', ['estado' => Venta::ESTADO_ANULADA]);

        $k = $this->generar(['desde' => '2026-11-03', 'hasta' => '2026-11-07'])['kpis'];

        $this->assertSame(2, $k['count']);
        $this->assertSame(330000.0, $k['total']);
        $this->assertSame(270000.0, $k['pagado']);
        $this->assertSame(60000.0, $k['saldo']);
        $this->assertSame(165000.0, $k['ticket_promedio']);
        $this->assertSame(230000.0, $k['contado_total']);
        $this->assertSame(1, $k['contado_count']);
        $this->assertSame(100000.0, $k['credito_total']);
        $this->assertSame(2, $k['clientes_count']);
        $this->assertSame(1, $k['anuladas_count']);
    }

    #[Test]
    public function agrupa_por_dia_con_los_dias_sin_ventas_y_por_semana_en_periodos_largos(): void
    {
        $cliente = Cliente::factory()->create();
        $this->venta($cliente, 50000, '2026-11-03');
        $this->venta($cliente, 20000, '2026-11-03');
        $this->venta($cliente, 80000, '2026-11-05');

        $dias = $this->generar(['desde' => '2026-11-03', 'hasta' => '2026-11-05'])['por_periodo'];

        $this->assertSame('dia', $dias['agrupacion']);
        $this->assertCount(3, $dias['filas']);
        $this->assertSame([70000.0, 0.0, 80000.0], array_column($dias['filas'], 'total'));
        $this->assertSame([2, 0, 1], array_column($dias['filas'], 'count'));
        $this->assertSame(80000.0, $dias['maximo']);
        $this->assertStringContainsString('03/11', $dias['filas'][0]['etiqueta']);

        $semanas = $this->generar(['desde' => '2026-10-01', 'hasta' => '2026-11-30'])['por_periodo'];

        $this->assertSame('semana', $semanas['agrupacion']);
        $this->assertSame(150000.0, array_sum(array_column($semanas['filas'], 'total')));
        $this->assertStringStartsWith('Sem.', $semanas['filas'][0]['etiqueta']);
    }

    #[Test]
    public function arma_el_top_de_clientes_con_participacion_y_saldo(): void
    {
        $a = Cliente::factory()->create(['nombre' => 'CLIENTE A', 'ciudad' => 'PELAYA']);
        $b = Cliente::factory()->create(['nombre' => 'CLIENTE B']);

        $this->venta($a, 300000, '2026-11-03');
        $pagada = $this->venta($b, 100000, '2026-11-04');
        $this->pagar($pagada, 100000);

        $top = $this->generar(['desde' => '2026-11-01', 'hasta' => '2026-11-30'])['top_clientes'];

        $this->assertSame('CLIENTE A', $top[0]['cliente']);
        $this->assertSame(75.0, $top[0]['participacion']);
        $this->assertSame(300000.0, $top[0]['saldo']);
        $this->assertSame('PELAYA', $top[0]['ciudad']);
        $this->assertSame(0.0, $top[1]['saldo']);
    }

    #[Test]
    public function respeta_el_filtro_de_cliente_y_toma_el_periodo_de_las_ventas(): void
    {
        $a = Cliente::factory()->create();
        $b = Cliente::factory()->create();
        $this->venta($a, 10000, '2026-11-02');
        $this->venta($a, 30000, '2026-11-06');
        $this->venta($b, 99000, '2026-11-04');

        $datos = $this->generar(['cliente_id' => $a->id]);

        $this->assertSame(40000.0, $datos['kpis']['total']);
        $this->assertSame('2026-11-02', $datos['desde']->toDateString());
        $this->assertSame('2026-11-06', $datos['hasta']->toDateString());
        $this->assertCount(2, $datos['detalle']);
    }

    #[Test]
    public function el_pdf_requiere_autenticacion(): void
    {
        $this->get('/ventas/exportar-pdf')->assertRedirect(route('login'));
    }

    #[Test]
    public function descarga_el_pdf_con_los_filtros_del_listado(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'TIENDA LA ESQUINA']);
        $this->venta($cliente, 250000, now()->toDateString());

        $desde = now()->startOfMonth()->toDateString();
        $hasta = now()->toDateString();

        $respuesta = $this->actingAs($this->user)->get("/ventas/exportar-pdf?desde={$desde}&hasta={$hasta}");

        $respuesta->assertOk();
        $respuesta->assertHeader('content-type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
        $this->assertStringContainsString(
            "reporte-ventas-{$desde}_a_{$hasta}.pdf",
            $respuesta->headers->get('content-disposition')
        );
    }

    #[Test]
    public function genera_el_pdf_aunque_no_haya_ventas(): void
    {
        $respuesta = $this->actingAs($this->user)->get('/ventas/exportar-pdf?desde=2020-01-01&hasta=2020-01-31');

        $respuesta->assertOk();
        $this->assertStringStartsWith('%PDF', $respuesta->getContent());
    }

    #[Test]
    public function el_listado_muestra_el_boton_pdf_con_los_filtros(): void
    {
        $this->actingAs($this->user)->get('/ventas?desde=2026-11-01')
            ->assertOk()
            ->assertSee(route('ventas.export-pdf', ['desde' => '2026-11-01']), false);
    }
}

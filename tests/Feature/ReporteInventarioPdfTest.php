<?php

namespace Tests\Feature;

use App\Models\InventarioMateriaPrima;
use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Models\Ruta;
use App\Models\User;
use App\Services\ReporteInventarioService;
use App\Services\ViajeCamionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ReporteInventarioPdfTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function materia(string $nombre, int $gramos, float $minimoKg, array $extra = []): MateriaPrima
    {
        $mp = MateriaPrima::factory()->create(array_merge(['nombre' => $nombre, 'stock_minimo' => $minimoKg, 'activo' => true], $extra));
        InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => $gramos, 'costo_promedio' => 0]);

        return $mp;
    }

    private function producto(string $nombre, int $unidades, int $minimo = 0, array $extra = []): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create(array_merge(['nombre' => $nombre, 'stock_minimo' => $minimo, 'activo' => true], $extra));
        InventarioProductoTerminado::create(['producto_terminado_id' => $pt->id, 'disponible' => $unidades, 'comprometido' => 0]);

        return $pt;
    }

    #[Test]
    public function materia_prima_con_estados_categorias_y_totales(): void
    {
        $this->materia('COMINO', 12500, 5, ['categoria' => 'Especias']);
        $this->materia('CANELA', 3000, 5, ['categoria' => 'Especias']);   // bajo mínimo
        $this->materia('MIEL', 0, 5, ['categoria' => 'Dulces']);           // agotada
        $this->materia('VIEJA', 0, 0, ['activo' => false]);                // inactiva sin stock: no aparece
        $this->materia('RESTO', 2000, 0, ['activo' => false, 'categoria' => null]); // inactiva con stock: sí aparece

        $datos = app(ReporteInventarioService::class)->materiaPrima();

        $this->assertSame(4, $datos['kpis']['articulos']);
        $this->assertSame(17.5, $datos['kpis']['total_kg']);
        $this->assertSame(1, $datos['kpis']['agotados']);
        $this->assertSame(1, $datos['kpis']['bajos']);
        $this->assertSame(2, $datos['kpis']['ok']);
        $this->assertSame(1, $datos['kpis']['inactivos']);

        // Categorías en orden alfabético y «Sin categoría» al final
        $this->assertSame(['Dulces', 'Especias', 'Sin categoría'], array_column($datos['grupos'], 'categoria'));
        $especias = $datos['grupos'][1];
        $this->assertSame(15.5, $especias['subtotal']['stock']);
        $this->assertSame(['bajo', 'ok'], array_column($especias['filas'], 'estado')); // CANELA, COMINO
    }

    #[Test]
    public function producto_terminado_suma_bodega_y_camion(): void
    {
        $comino = $this->producto('COMINO', 400, 0, ['categoria' => 'ESP', 'peso_neto' => 20]);
        $this->producto('COMINO', 60, 0, ['categoria' => 'B2C', 'peso_neto' => 12]); // misma fila, columna B2C
        $this->producto('CANELA', 0, 0, ['categoria' => 'ESP']);   // agotado
        $this->producto('ANIS', 30, 40, ['categoria' => 'B2C']);   // bajo mínimo

        // 5 paquetes de comino Especial al camión
        $ruta = Ruta::factory()->create();
        app(ViajeCamionService::class)->cargar(['ruta_id' => $ruta->id, 'lineas' => [$comino->id => ['surtido' => 100]]]);

        $datos = app(ReporteInventarioService::class)->productoTerminado();

        $this->assertSame(390, $datos['kpis']['bodega']); // 300 + 60 + 0 + 30
        $this->assertSame(100, $datos['kpis']['camion']);
        $this->assertSame(490, $datos['kpis']['total']);
        $this->assertSame(3, $datos['kpis']['productos']);
        $this->assertSame(4, $datos['kpis']['articulos']); // presentaciones
        $this->assertSame(1, $datos['kpis']['agotados']);
        $this->assertSame(1, $datos['kpis']['bajos']);
        $this->assertSame(['B2C' => 90, 'ESP' => 400], $datos['kpis']['por_linea']);

        // Una fila por producto con las líneas en columnas
        $this->assertSame(['ANIS', 'CANELA', 'COMINO'], array_column($datos['filas'], 'nombre'));
        $filaComino = collect($datos['filas'])->firstWhere('nombre', 'COMINO');
        $this->assertSame(400, $filaComino['lineas']['ESP']['total']); // 300 bodega + 100 camión
        $this->assertSame(60, $filaComino['lineas']['B2C']['total']);
        $this->assertSame(100, $filaComino['camion']);
        $this->assertSame(460, $filaComino['total']);
    }

    #[Test]
    public function los_reportes_de_inventario_requieren_autenticacion(): void
    {
        auth()->logout();

        $this->get('/inventario/materia-prima/reporte-pdf')->assertRedirect(route('login'));
        $this->get('/inventario/productos-terminados/reporte-pdf')->assertRedirect(route('login'));
    }

    #[Test]
    public function descarga_los_dos_pdf_con_la_fecha_de_corte(): void
    {
        $this->materia('COMINO', 12500, 5);
        $this->producto('COMINO', 400);
        $hoy = now()->format('Y-m-d');

        foreach ([
            '/inventario/materia-prima/reporte-pdf' => "inventario-materia-prima-{$hoy}.pdf",
            '/inventario/productos-terminados/reporte-pdf' => "inventario-producto-terminado-{$hoy}.pdf",
        ] as $url => $archivo) {
            $respuesta = $this->get($url);

            $respuesta->assertOk();
            $respuesta->assertHeader('content-type', 'application/pdf');
            $this->assertStringStartsWith('%PDF', $respuesta->getContent());
            $this->assertStringContainsString($archivo, $respuesta->headers->get('content-disposition'));
        }
    }

    #[Test]
    public function genera_los_pdf_aunque_no_haya_articulos(): void
    {
        $this->get('/inventario/materia-prima/reporte-pdf')->assertOk();
        $this->get('/inventario/productos-terminados/reporte-pdf')->assertOk();
    }

    #[Test]
    public function los_listados_muestran_el_boton_pdf(): void
    {
        $this->get('/inventario/materia-prima')->assertOk()
            ->assertSee(route('inventario.materia-prima.export-pdf'), false);
        $this->get('/inventario/productos-terminados')->assertOk()
            ->assertSee(route('inventario.productos-terminados.export-pdf'), false);
    }

    #[Test]
    public function el_listado_y_el_detalle_muestran_el_stock_en_paquetes(): void
    {
        $pt = $this->producto('COMINO', 245);

        $this->get('/inventario/productos-terminados')->assertOk()->assertSee('12 p + 5 u');
        $this->get("/inventario/productos-terminados/{$pt->producto_id}")->assertOk()->assertSee('12 p + 5 u')->assertSee('245 u');
    }

    #[Test]
    public function el_listado_de_materia_prima_trae_el_buscador(): void
    {
        $this->materia('CÚRCUMA', 1000, 0, ['codigo' => 'MP-CUR', 'categoria' => 'Especias', 'proveedor' => 'La Huerta']);

        $this->get('/inventario/materia-prima?q=curcuma')->assertOk()
            ->assertSee('id="buscador" value="curcuma"', false)
            ->assertSee('data-buscar="curcuma mp-cur especias la huerta"', false)
            ->assertSee('1 materias primas');
    }

    #[Test]
    public function la_ruta_del_reporte_no_choca_con_el_detalle_del_articulo(): void
    {
        $mp = $this->materia('COMINO', 1000, 0);

        $this->get("/inventario/materia-prima/{$mp->id}")->assertOk()->assertSee('COMINO');
    }
}

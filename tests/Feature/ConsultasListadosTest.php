<?php

namespace Tests\Feature;

use App\Models\InventarioMateriaPrima;
use App\Models\InventarioProductoTerminado;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Models\User;
use App\Services\MovimientoInventarioService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Las pantallas no deben hacer una consulta por fila (N+1): con la base en RDS
 * cada consulta suma latencia. El número de consultas no puede crecer con los datos.
 */
class ConsultasListadosTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const PANTALLAS = [
        '/gestion',
        '/inventario/materia-prima',
        '/inventario/productos-terminados',
        '/inventario/produccion/crear',
        '/inventario/recetas',
        '/inventario/movimientos/crear',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
    }

    private function crearArticulos(int $cantidad): void
    {
        foreach (range(1, $cantidad) as $i) {
            $mp = MateriaPrima::factory()->create(['activo' => true]);
            InventarioMateriaPrima::create(['materia_prima_id' => $mp->id, 'stock_gramos' => 5000, 'costo_promedio' => 0]);

            $pt = ProductoTerminado::factory()->create(['activo' => true]);
            InventarioProductoTerminado::create(['producto_terminado_id' => $pt->id, 'disponible' => 0, 'comprometido' => 0]);
            $pt->receta()->create(['materia_prima_id' => $mp->id, 'gramos_por_unidad' => 5]);

            app(MovimientoInventarioService::class)->registrar([
                'origen' => $mp, 'tipo' => 'ajuste_positivo', 'cantidad' => 100, 'motivo' => 'prueba',
            ]);
        }
    }

    /** @return array<string, int> */
    private function contarConsultas(): array
    {
        $conteo = [];
        foreach (self::PANTALLAS as $url) {
            $n = 0;
            DB::listen(function () use (&$n) { $n++; });
            $this->get($url)->assertOk();
            $conteo[$url] = $n;
            DB::flushQueryLog();
            DB::getEventDispatcher()->forget(\Illuminate\Database\Events\QueryExecuted::class);
        }

        return $conteo;
    }

    #[Test]
    public function las_consultas_no_crecen_con_la_cantidad_de_articulos(): void
    {
        $this->crearArticulos(2);
        $pocos = $this->contarConsultas();

        $this->crearArticulos(8);
        $muchos = $this->contarConsultas();

        $this->assertSame($pocos, $muchos);
    }
}

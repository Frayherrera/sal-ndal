<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use Database\Seeders\GastoRutaSeeder;
use Database\Seeders\GastoSantiniSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GastoSeedersTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function crea_los_gastos_de_ruta_en_su_categoria_sin_duplicar(): void
    {
        $this->seed(GastoRutaSeeder::class);
        $this->seed(GastoRutaSeeder::class); // segunda corrida: no debe duplicar

        $categoria = CategoriaGasto::where('nombre', 'Ruta')->firstOrFail();
        $gastos = Gasto::where('categoria_gasto_id', $categoria->id)->get();

        $this->assertCount(11, $gastos);
        $this->assertSame(11, Gasto::count());
        $this->assertSame(612927.0, round((float) $gastos->sum('monto'), 2));
        $this->assertSame(5, $gastos->where('descripcion', 'Comida')->count());
        $this->assertTrue($gastos->every(fn (Gasto $g) => $g->estado === Gasto::ESTADO_REGISTRADO && $g->numero !== null));
        $this->assertSame('2026-11-03', $gastos->min(fn (Gasto $g) => $g->fecha->toDateString()));
        $this->assertSame('2026-11-07', $gastos->max(fn (Gasto $g) => $g->fecha->toDateString()));
    }

    #[Test]
    public function crea_los_gastos_de_santini_en_su_categoria_sin_duplicar(): void
    {
        $this->seed(GastoSantiniSeeder::class);
        $this->seed(GastoSantiniSeeder::class);

        $categoria = CategoriaGasto::where('nombre', 'Santini')->firstOrFail();
        $gastos = Gasto::where('categoria_gasto_id', $categoria->id)->get();

        $this->assertCount(10, $gastos);
        $this->assertSame(10, Gasto::count());
        $this->assertSame(2665200.0, round((float) $gastos->sum('monto'), 2));
        $this->assertSame('2026-11-08', $gastos->min(fn (Gasto $g) => $g->fecha->toDateString()));
        $this->assertSame('2026-11-11', $gastos->max(fn (Gasto $g) => $g->fecha->toDateString()));
    }
}

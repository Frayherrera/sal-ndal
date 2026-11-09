<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CarteraFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function venta(Cliente $cliente, array $atributos = []): Venta
    {
        $venta = Venta::factory()->create(array_merge([
            'cliente_id' => $cliente->id,
            'user_id' => $this->user->id,
            'subtotal' => 50000,
            'total' => 50000,
            'estado' => Venta::ESTADO_CONFIRMADA,
            'fecha_vencimiento' => now()->addDays(10)->toDateString(),
        ], $atributos));

        $venta->numero = 'VTA-'.str_pad((string) $venta->id, 5, '0', STR_PAD_LEFT);
        $venta->save();

        return $venta;
    }

    #[Test]
    public function la_cartera_requiere_autenticacion(): void
    {
        $this->get('/ventas/cartera')->assertRedirect(route('login'));
    }

    #[Test]
    public function muestra_solo_ventas_con_saldo_pendiente(): void
    {
        $pendiente = $this->venta(Cliente::factory()->create(['nombre' => 'Cliente Pendiente']));
        $pagada = $this->venta(Cliente::factory()->create(['nombre' => 'Cliente Pagado']));

        $pagada->pagos()->create([
            'fecha' => now(),
            'monto' => 50000,
            'metodo' => 'efectivo',
            'user_id' => $this->user->id,
        ]);

        $this->actingAs($this->user)
            ->get('/ventas/cartera')
            ->assertOk()
            ->assertSee($pendiente->numero)
            ->assertDontSee($pagada->numero)
            ->assertSee('$50.000');
    }

    #[Test]
    public function filtra_solo_las_ventas_vencidas(): void
    {
        $vencida = $this->venta(Cliente::factory()->create(['nombre' => 'Cliente Vencido']), [
            'fecha_vencimiento' => now()->subDay()->toDateString(),
        ]);

        $vigente = $this->venta(Cliente::factory()->create(['nombre' => 'Cliente Vigente']), [
            'fecha_vencimiento' => now()->addDays(10)->toDateString(),
        ]);

        $this->actingAs($this->user)
            ->get('/ventas/cartera?vencidas=1')
            ->assertOk()
            ->assertSee($vencida->numero)
            ->assertDontSee($vigente->numero);

        $this->assertTrue($vencida->estaVencida());
        $this->assertFalse($vigente->estaVencida());
    }

    #[Test]
    public function registra_un_abono_y_reduce_el_saldo(): void
    {
        $venta = $this->venta(Cliente::factory()->create());

        $this->actingAs($this->user)
            ->post("/ventas/{$venta->id}/abonos", [
                'monto' => 20000,
                'metodo' => 'nequi',
                'referencia' => 'REF-123',
                'fecha' => now()->toDateString(),
            ])
            ->assertRedirect(route('ventas.show', $venta));

        $this->assertDatabaseHas('pago_venta', [
            'venta_id' => $venta->id,
            'monto' => 20000,
            'metodo' => 'nequi',
            'referencia' => 'REF-123',
            'user_id' => $this->user->id,
        ]);
        $this->assertSame(30000.0, $venta->fresh()->saldo());
    }

    #[Test]
    public function no_permite_abonos_mayores_al_saldo(): void
    {
        $venta = $this->venta(Cliente::factory()->create());

        $this->actingAs($this->user)
            ->from("/ventas/{$venta->id}/abonos/crear")
            ->post("/ventas/{$venta->id}/abonos", [
                'monto' => 90000,
                'metodo' => 'efectivo',
            ])
            ->assertRedirect("/ventas/{$venta->id}/abonos/crear")
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pago_venta', 0);
        $this->assertSame(50000.0, $venta->fresh()->saldo());
    }

    #[Test]
    public function no_permite_abonar_a_una_venta_anulada(): void
    {
        $venta = $this->venta(Cliente::factory()->create(), ['estado' => Venta::ESTADO_ANULADA]);

        $this->actingAs($this->user)
            ->from("/ventas/{$venta->id}/abonos/crear")
            ->post("/ventas/{$venta->id}/abonos", [
                'monto' => 1000,
                'metodo' => 'efectivo',
            ])
            ->assertRedirect("/ventas/{$venta->id}/abonos/crear")
            ->assertSessionHas('error');

        $this->assertDatabaseCount('pago_venta', 0);
    }

    #[Test]
    public function el_formulario_de_abono_se_renderiza(): void
    {
        $venta = $this->venta(Cliente::factory()->create(['nombre' => 'Cliente Abono']));

        $this->actingAs($this->user)
            ->get("/ventas/{$venta->id}/abonos/crear")
            ->assertOk()
            ->assertSee('Registrar Abono')
            ->assertSee('Cliente Abono')
            ->assertSee('$50.000');
    }
}

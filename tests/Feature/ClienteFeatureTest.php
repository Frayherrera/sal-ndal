<?php

namespace Tests\Feature;

use App\Models\Cliente;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ClienteFeatureTest extends TestCase
{
    use LazilyRefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    #[Test]
    public function las_rutas_de_clientes_requieren_autenticacion(): void
    {
        $this->get('/clientes')->assertRedirect(route('login'));
        $this->get('/clientes/create')->assertRedirect(route('login'));
    }

    #[Test]
    public function lista_clientes(): void
    {
        Cliente::factory()->create(['nombre' => 'María Gómez', 'ciudad' => 'Bogotá']);

        $this->actingAs($this->user)
            ->get('/clientes')
            ->assertOk()
            ->assertSee('María Gómez')
            ->assertSee('Bogotá');
    }

    #[Test]
    public function crea_un_cliente(): void
    {
        $this->actingAs($this->user)
            ->post('/clientes', [
                'nombre' => 'Supermercado La 80',
                'local' => 'Sede Norte',
                'ciudad' => 'Medellín',
                'telefono' => '300 123 4567',
            ])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Supermercado La 80',
            'local' => 'Sede Norte',
            'ciudad' => 'Medellín',
            'telefono' => '300 123 4567',
        ]);
    }

    #[Test]
    public function crea_un_cliente_solo_con_nombre(): void
    {
        $this->actingAs($this->user)
            ->post('/clientes', ['nombre' => 'Cliente Sin Datos'])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'nombre' => 'Cliente Sin Datos',
            'local' => null,
            'ciudad' => null,
            'telefono' => null,
        ]);
    }

    #[Test]
    public function no_crea_un_cliente_sin_nombre(): void
    {
        $this->actingAs($this->user)
            ->post('/clientes', ['ciudad' => 'Cali'])
            ->assertSessionHasErrors('nombre');
    }

    #[Test]
    public function actualiza_un_cliente(): void
    {
        $cliente = Cliente::factory()->create(['nombre' => 'Original']);

        $this->actingAs($this->user)
            ->put("/clientes/{$cliente->id}", [
                'nombre' => 'Actualizado',
                'local' => 'Local Nuevo',
                'ciudad' => 'Pereira',
                'telefono' => '310 000 0000',
            ])
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseHas('clientes', [
            'id' => $cliente->id,
            'nombre' => 'Actualizado',
            'local' => 'Local Nuevo',
            'ciudad' => 'Pereira',
            'telefono' => '310 000 0000',
        ]);
    }

    #[Test]
    public function elimina_un_cliente(): void
    {
        $cliente = Cliente::factory()->create();

        $this->actingAs($this->user)
            ->delete("/clientes/{$cliente->id}")
            ->assertRedirect(route('clientes.index'));

        $this->assertDatabaseMissing('clientes', ['id' => $cliente->id]);
    }
}

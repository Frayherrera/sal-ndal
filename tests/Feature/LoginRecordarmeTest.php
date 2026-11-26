<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LoginRecordarmeTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[Test]
    public function con_recordarme_guarda_la_cookie_y_el_token(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secreto123')]);

        $respuesta = $this->post('/login', ['email' => $user->email, 'password' => 'secreto123', 'remember' => '1']);

        $respuesta->assertRedirect('/gestion');
        $respuesta->assertCookie(Auth::guard()->getRecallerName());
        $this->assertNotNull($user->fresh()->remember_token);
    }

    #[Test]
    public function sin_recordarme_no_guarda_la_cookie(): void
    {
        $user = User::factory()->create(['password' => bcrypt('secreto123')]);

        $respuesta = $this->post('/login', ['email' => $user->email, 'password' => 'secreto123']);

        $respuesta->assertRedirect('/gestion');
        $respuesta->assertCookieMissing(Auth::guard()->getRecallerName());
    }

    #[Test]
    public function la_casilla_viene_marcada_por_defecto(): void
    {
        $this->get('/login')->assertOk()->assertSee('name="remember" value="1" checked', false);
    }
}

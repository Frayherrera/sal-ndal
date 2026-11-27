<?php

namespace Tests\Feature;

use App\Models\InventarioProductoTerminado;
use App\Models\ProductoTerminado;
use App\Models\Ruta;
use App\Models\User;
use App\Services\Telegram\IA\ModeloIA;
use App\Services\Telegram\IA\RespuestaIA;
use App\Services\ViajeCamionService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Consultas del camión desde el bot: comandos y funciones del asistente con IA.
 */
class TelegramCamionTest extends TestCase
{
    use LazilyRefreshDatabase;

    private const CHAT = 111;

    private int $updateId = 1;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.token' => 'TOKEN-DE-PRUEBA',
            'services.telegram.webhook_secret' => 'secreto-webhook',
            'services.telegram.allowed_chat_ids' => [(string) self::CHAT],
        ]);

        $this->actingAs(User::factory()->create());

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]])]);
    }

    private function enviarTexto(string $texto)
    {
        return $this->postJson('/telegram/webhook', [
            'update_id' => $this->updateId++,
            'message' => ['message_id' => 10, 'chat' => ['id' => self::CHAT], 'text' => $texto],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secreto-webhook']);
    }

    private function ultimaRespuesta(): string
    {
        return collect(Http::recorded())->map(fn ($par) => $par[0])
            ->filter(fn (Request $r) => str_contains($r->url(), 'sendMessage'))
            ->last()['text'];
    }

    private function producto(string $nombre): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create(['nombre' => $nombre, 'categoria' => 'ESP', 'peso_neto' => 20, 'precio_venta' => 24000, 'activo' => true]);
        InventarioProductoTerminado::create(['producto_terminado_id' => $pt->id, 'disponible' => 2000, 'comprometido' => 0]);

        return $pt;
    }

    /**
     * Dos viajes cerrados y el camión en casa con lo que regresó del segundo.
     *
     * @return array{0: ProductoTerminado, 1: ProductoTerminado, 2: Ruta, 3: Ruta}
     */
    private function escenario(): array
    {
        $servicio = app(ViajeCamionService::class);
        $pelaya = Ruta::factory()->create(['nombre' => 'Pelaya', 'orden' => 1]);
        $gloria = Ruta::factory()->create(['nombre' => 'La Gloria', 'orden' => 2]);
        $comino = $this->producto('Comino molido');
        $canela = $this->producto('Canela en polvo');

        // VJE-00001 Pelaya: comino 30 p, canela 10 p → vende 20 p de comino y 6 p de canela, 1 p de comino dañado
        $v1 = $servicio->cargar(['ruta_id' => $pelaya->id, 'fecha_salida' => now()->subDays(10)->toDateString(),
            'lineas' => [$comino->id => ['surtido' => 600], $canela->id => ['surtido' => 200]]]);
        $servicio->cerrar($v1, ['fecha_regreso' => now()->subDays(8)->toDateString(), 'lineas' => [
            $comino->id => ['regresado' => 180, 'danado' => 20], $canela->id => ['regresado' => 80],
        ]]);

        // VJE-00002 La Gloria: sale con lo que volvió + 10 p de comino → vende 15 p de comino
        $v2 = $servicio->cargar(['ruta_id' => $gloria->id, 'fecha_salida' => now()->subDays(3)->toDateString(),
            'lineas' => [$comino->id => ['surtido' => 200]]]);
        $servicio->cerrar($v2, ['fecha_regreso' => now()->subDay()->toDateString(), 'lineas' => [
            $comino->id => ['regresado' => 80], $canela->id => ['regresado' => 80],
        ]]);

        return [$comino, $canela, $pelaya, $gloria];
    }

    #[Test]
    public function camion_muestra_si_esta_en_casa_o_en_ruta(): void
    {
        $this->enviarTexto('/camion');
        $this->assertStringContainsString('Está vacío', $this->ultimaRespuesta());

        [$comino, , $pelaya] = $this->escenario();

        $this->enviarTexto('/camion');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('El camión está en casa', $texto);
        $this->assertStringContainsString('Comino molido · Especial 20 g: 4 p', $texto);
        $this->assertStringContainsString('Siguiente ruta en la rotación: <b>Pelaya</b>', $texto);

        app(ViajeCamionService::class)->cargar(['ruta_id' => $pelaya->id, 'lineas' => [$comino->id => ['surtido' => 100]]]);

        $this->enviarTexto('/camion');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('El camión está en ruta', $texto);
        $this->assertStringContainsString('VJE-00003', $texto);
        $this->assertStringContainsString('Comino molido · Especial 20 g: 9 p', $texto); // 80 + 100 = 180 u
    }

    #[Test]
    public function vendido_del_ultimo_viaje_o_de_uno_especifico(): void
    {
        $this->enviarTexto('/vendido');
        $this->assertStringContainsString('Todavía no hay viajes cerrados', $this->ultimaRespuesta());

        $this->escenario();

        $this->enviarTexto('/vendido');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Vendido en VJE-00002', $texto);
        $this->assertStringContainsString('Comino molido · Especial 20 g: <b>15 p</b> · $360.000', $texto);
        $this->assertStringNotContainsString('Canela en polvo:', $texto); // no se vendió canela

        $this->enviarTexto('/vendido 1');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Vendido en VJE-00001', $texto);
        $this->assertStringContainsString('Comino molido · Especial 20 g: <b>20 p</b>', $texto);
        $this->assertStringContainsString('Canela en polvo · Especial 20 g: <b>6 p</b>', $texto);
        $this->assertStringContainsString('1 p dañado', $texto);

        $this->enviarTexto('/vendido VJE-00099');
        $this->assertStringContainsString('No encontré el viaje', $this->ultimaRespuesta());
    }

    #[Test]
    public function semanas_resume_los_viajes_y_un_producto(): void
    {
        $this->escenario();

        $this->enviarTexto('/semanas');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Vendido en los últimos 2 viajes', $texto);
        $this->assertStringContainsString('Pelaya: <b>26 p</b>', $texto);
        $this->assertStringContainsString('La Gloria: <b>15 p</b>', $texto);

        $this->enviarTexto('/semanas comino');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('<b>Comino molido · Especial 20 g</b>', $texto);
        $this->assertStringContainsString('Pelaya: 20 p', $texto);
        $this->assertStringContainsString('La Gloria: 15 p', $texto);
        $this->assertStringContainsString('Total: <b>35 p</b>', $texto);

        $this->enviarTexto('/semanas oregano');
        $this->assertStringContainsString('Ningún producto', $this->ultimaRespuesta());
    }

    #[Test]
    public function crear_una_ruta_pide_confirmacion_y_va_al_final_de_la_rotacion(): void
    {
        \App\Models\Cliente::factory()->create(['ciudad' => 'GAMARRA']);
        Ruta::factory()->create(['nombre' => 'aguachica - gamarra', 'orden' => 1]);

        $this->enviarTexto('/ruta Gamarra | gamarra, Aguachica');

        $conBotones = collect(Http::recorded())->map(fn ($par) => $par[0])
            ->first(fn (Request $r) => isset($r['reply_markup']));
        $this->assertNotNull($conBotones);
        $this->assertStringContainsString('Nombre: <b>Gamarra</b>', $conBotones['text']);
        $this->assertStringContainsString('Orden en la rotación: 2', $conBotones['text']);
        $this->assertStringContainsString('GAMARRA, AGUACHICA', $conBotones['text']); // usa la escritura de los clientes
        $this->assertDatabaseCount('rutas', 1); // todavía no se guarda

        $this->postJson('/telegram/webhook', [
            'update_id' => $this->updateId++,
            'callback_query' => [
                'id' => 'cb1',
                'message' => ['message_id' => 99, 'chat' => ['id' => self::CHAT]],
                'data' => $conBotones['reply_markup']['inline_keyboard'][0][0]['callback_data'],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secreto-webhook'])->assertOk();

        $ruta = Ruta::where('nombre', 'Gamarra')->firstOrFail();
        $this->assertSame(2, $ruta->orden);
        $this->assertSame(['GAMARRA', 'AGUACHICA'], $ruta->ciudades);
        $this->assertTrue($ruta->activo);

        // Nombres repetidos se rechazan (sin distinguir mayúsculas ni tildes)
        $this->enviarTexto('/ruta GAMARRÁ');
        $this->assertStringContainsString('Ya existe la ruta', $this->ultimaRespuesta());

        $this->enviarTexto('/ruta');
        $this->assertStringContainsString('Indica el nombre de la ruta', $this->ultimaRespuesta());

        // Listado en orden de rotación
        $this->enviarTexto('/rutas');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('1. <b>aguachica - gamarra</b>', $texto);
        $this->assertStringContainsString('2. <b>Gamarra</b> · GAMARRA, AGUACHICA', $texto);
    }

    #[Test]
    public function el_asistente_con_ia_puede_preparar_una_ruta(): void
    {
        $modelo = new ModeloCamionSimulado([
            ['preparar_ruta', ['nombre' => 'Gamarra']],
            'Listo, revisa el resumen y confirma la ruta Gamarra.',
        ]);
        $this->app->instance(ModeloIA::class, $modelo);

        $this->enviarTexto('crea una nueva ruta que se llame Gamarra');

        $this->assertContains('preparar_ruta', $modelo->funcionesOfrecidas);
        $this->assertContains('listar_rutas', $modelo->funcionesOfrecidas);
        $this->assertStringContainsString('TODAVÍA NO ESTÁ GUARDADO', $modelo->resultados[0]['resultado']);
        $this->assertDatabaseCount('rutas', 0);
        $this->assertSame('Listo, revisa el resumen y confirma la ruta Gamarra.', $this->ultimaRespuesta());
    }

    #[Test]
    public function la_ayuda_y_el_menu_incluyen_el_camion(): void
    {
        $this->enviarTexto('/ayuda');
        $this->assertStringContainsString('/camion', $this->ultimaRespuesta());
        $this->assertArrayHasKey('semanas', \App\Services\Telegram\TelegramBot::COMANDOS);
    }

    #[Test]
    public function el_asistente_con_ia_usa_las_funciones_del_camion(): void
    {
        $this->escenario();

        $modelo = new ModeloCamionSimulado([
            ['vendido_por_semana', ['producto' => 'comino']],
            'Esta semana se vendieron 15 paquetes de comino en La Gloria.',
        ]);
        $this->app->instance(ModeloIA::class, $modelo);

        $this->enviarTexto('cuánto comino se vende por semana?');

        $this->assertContains('vendido_por_semana', $modelo->funcionesOfrecidas);
        $this->assertContains('estado_camion', $modelo->funcionesOfrecidas);
        $this->assertStringContainsString('Total: 35 p', $modelo->resultados[0]['resultado']);
        $this->assertSame('Esta semana se vendieron 15 paquetes de comino en La Gloria.', $this->ultimaRespuesta());
    }
}

/**
 * Modelo de IA simulado para este test: cada paso del guion es una llamada a función
 * [nombre, args] o el texto final.
 */
class ModeloCamionSimulado implements ModeloIA
{
    /** @var array<int, string> */
    public array $funcionesOfrecidas = [];

    /** @var array<int, array<string, string>> */
    public array $resultados = [];

    public function __construct(private array $guion) {}

    public function configurado(): bool
    {
        return true;
    }

    public function responder(string $instrucciones, array $funciones, array $conversacion): RespuestaIA
    {
        $this->funcionesOfrecidas = array_column($funciones, 'name');

        foreach (end($conversacion)['parts'] ?? [] as $parte) {
            if (isset($parte['functionResponse'])) {
                $this->resultados[] = $parte['functionResponse']['response'];
            }
        }

        $paso = array_shift($this->guion) ?? '';

        if (is_array($paso)) {
            [$nombre, $args] = $paso;

            return new RespuestaIA('STOP', '', [['name' => $nombre, 'args' => $args]],
                ['role' => 'model', 'parts' => [['functionCall' => ['name' => $nombre, 'args' => $args]]]]);
        }

        return new RespuestaIA('STOP', $paso, [], ['role' => 'model', 'parts' => [['text' => $paso]]]);
    }
}

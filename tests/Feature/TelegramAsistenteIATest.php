<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\User;
use App\Models\Venta;
use App\Services\Telegram\IA\GeminiModeloIA;
use App\Services\Telegram\IA\LimiteIAException;
use App\Services\Telegram\IA\ModeloIA;
use App\Services\Telegram\IA\RespuestaIA;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Asistente con IA del bot. El modelo se simula con respuestas guionadas,
 * así que no se llama a Gemini.
 */
class TelegramAsistenteIATest extends TestCase
{
    use LazilyRefreshDatabase;

    private const CHAT = 111;

    private ModeloSimulado $modelo;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'services.telegram.token' => 'TOKEN-DE-PRUEBA',
            'services.telegram.webhook_secret' => 'secreto-webhook',
            'services.telegram.allowed_chat_ids' => [(string) self::CHAT],
            'services.telegram.user_email' => 'bot@santini.test',
        ]);

        User::factory()->create(['email' => 'bot@santini.test']);

        Http::fake(['api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]])]);

        $this->modelo = new ModeloSimulado;
        $this->app->instance(ModeloIA::class, $this->modelo);
    }

    private function enviarTexto(string $texto, int $updateId = 1)
    {
        return $this->postJson('/telegram/webhook', [
            'update_id' => $updateId,
            'message' => ['message_id' => 10, 'chat' => ['id' => self::CHAT], 'text' => $texto],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secreto-webhook']);
    }

    private function enviados(): \Illuminate\Support\Collection
    {
        return collect(Http::recorded())->map(fn ($par) => $par[0])
            ->filter(fn (Request $r) => str_contains($r->url(), 'sendMessage') || str_contains($r->url(), 'editMessageText'))
            ->values();
    }

    #[Test]
    public function responde_una_consulta_usando_las_funciones(): void
    {
        $yurani = Cliente::factory()->create(['nombre' => 'YURANI CHINCHILLA']);
        Venta::factory()->create(['cliente_id' => $yurani->id, 'subtotal' => 52000, 'total' => 52000,
            'forma_pago' => 'credito', 'fecha_vencimiento' => now()->addDays(30)]);

        $this->modelo->guion = [
            RespuestaSimulada::llamada('cartera', ['cliente' => 'yurani']),
            RespuestaSimulada::texto('Yurani debe <b>$52.000</b>.'),
        ];

        $this->enviarTexto('¿cuánto me debe yurani?')->assertOk();

        // La función recibió el saldo real desde la base
        $resultado = $this->modelo->resultadosRecibidos()[0];
        $this->assertStringContainsString('Debe: $52.000', $resultado['resultado']);
        $this->assertStringNotContainsString('<b>', $resultado['resultado']); // al modelo le llega texto plano

        // La respuesta del modelo se envía escapada (no se interpreta como HTML)
        $this->assertSame('Yurani debe &lt;b&gt;$52.000&lt;/b&gt;.', $this->enviados()->last()['text']);

        // Muestra "escribiendo…" mientras procesa
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'sendChatAction'));
    }

    #[Test]
    public function un_registro_por_ia_pide_confirmacion_y_no_guarda_hasta_confirmar(): void
    {
        CategoriaGasto::factory()->create(['nombre' => 'Ruta']);

        $this->modelo->guion = [
            RespuestaSimulada::llamada('preparar_gasto', ['valor' => 70000, 'descripcion' => 'almuerzo', 'categoria' => 'ruta']),
            RespuestaSimulada::texto('Revisa el resumen y confírmalo.'),
        ];

        $this->enviarTexto('anota 70 mil de almuerzo en ruta');

        $this->assertDatabaseCount('gastos', 0);
        $this->assertStringContainsString('TODAVÍA NO ESTÁ GUARDADO', $this->modelo->resultadosRecibidos()[0]['resultado']);

        $confirmacion = $this->enviados()->first(fn (Request $r) => isset($r['reply_markup']));
        $this->assertNotNull($confirmacion);
        $this->assertStringContainsString('Valor: <b>$70.000</b>', $confirmacion['text']);
        $this->assertStringContainsString('Categoría: Ruta', $confirmacion['text']);

        $this->postJson('/telegram/webhook', [
            'update_id' => 2,
            'callback_query' => [
                'id' => 'cb1',
                'message' => ['message_id' => 99, 'chat' => ['id' => self::CHAT]],
                'data' => $confirmacion['reply_markup']['inline_keyboard'][0][0]['callback_data'],
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secreto-webhook']);

        $gasto = Gasto::firstOrFail();
        $this->assertSame(70000.0, (float) $gasto->monto);
        $this->assertSame('Almuerzo', $gasto->descripcion);
    }

    #[Test]
    public function los_errores_de_las_funciones_vuelven_al_modelo(): void
    {
        Cliente::factory()->create(['nombre' => 'RAMON PACHECO']);
        Cliente::factory()->create(['nombre' => 'MILADYS PACHECO']);

        $this->modelo->guion = [
            RespuestaSimulada::llamada('preparar_venta', ['cliente' => 'pacheco', 'valor' => 50000, 'forma_pago' => 'contado']),
            RespuestaSimulada::texto('Hay dos Pacheco, ¿cuál?'),
        ];

        $this->enviarTexto('véndele 50 mil a pacheco');

        $error = $this->modelo->resultadosRecibidos()[0]['error'];
        $this->assertStringContainsString('Hay varios clientes', $error);
        $this->assertDatabaseCount('ventas', 0);
        $this->assertSame('Hay dos Pacheco, ¿cuál?', $this->enviados()->last()['text']);
    }

    #[Test]
    public function recuerda_la_conversacion_reciente(): void
    {
        $this->modelo->guion = [RespuestaSimulada::texto('Hola, ¿en qué te ayudo?')];
        $this->enviarTexto('hola', 1);

        $this->modelo->guion = [RespuestaSimulada::texto('Claro.')];
        $this->enviarTexto('y ayer?', 2);

        $ultimoPedido = $this->modelo->conversaciones[array_key_last($this->modelo->conversaciones)];
        $texto = $ultimoPedido[0]['parts'][0]['text'];

        $this->assertStringContainsString('Usuario: hola', $texto);
        $this->assertStringContainsString('Asistente: Hola, ¿en qué te ayudo?', $texto);
        $this->assertStringEndsWith('y ayer?', $texto);
    }

    #[Test]
    public function avisa_cuando_se_acaba_el_limite_gratuito(): void
    {
        $this->modelo->excepcion = new LimiteIAException('límite');

        $this->enviarTexto('hola');

        $this->assertStringContainsString('límite gratuito', $this->enviados()->last()['text']);
    }

    #[Test]
    public function los_comandos_con_barra_no_pasan_por_la_ia(): void
    {
        $this->enviarTexto('/resumen');

        $this->assertSame([], $this->modelo->conversaciones);
        $this->assertStringContainsString('Resumen', $this->enviados()->last()['text']);
    }

    #[Test]
    public function sin_key_el_texto_libre_sugiere_los_comandos(): void
    {
        $this->modelo->disponible = false;

        $this->enviarTexto('hola');

        $this->assertSame([], $this->modelo->conversaciones);
        $this->assertStringContainsString('/ayuda', $this->enviados()->last()['text']);
    }

    #[Test]
    public function un_update_repetido_por_telegram_se_procesa_una_sola_vez(): void
    {
        $this->modelo->guion = [RespuestaSimulada::texto('Respuesta.')];

        $this->enviarTexto('hola', 7);
        $this->enviarTexto('hola', 7);

        $this->assertCount(1, $this->modelo->conversaciones);
    }

    #[Test]
    public function el_cliente_de_gemini_usa_el_siguiente_modelo_si_uno_esta_saturado(): void
    {
        Http::fake([
            '*models/modelo-a:generateContent' => Http::response(['error' => ['message' => 'high demand']], 503),
            '*models/modelo-b:generateContent' => Http::response(['candidates' => [[
                'finishReason' => 'STOP',
                'content' => ['role' => 'model', 'parts' => [
                    ['functionCall' => ['name' => 'alertas_stock', 'args' => []]],
                    ['text' => 'Listo'],
                ]],
            ]]]),
        ]);

        $respuesta = (new GeminiModeloIA('KEY-PRUEBA', ['modelo-a', 'modelo-b']))
            ->responder('instrucciones', [['name' => 'alertas_stock']], [['role' => 'user', 'parts' => [['text' => 'hola']]]]);

        $this->assertSame('Listo', $respuesta->texto);
        $this->assertSame('alertas_stock', $respuesta->llamadas[0]['name']);
        // Los argumentos vacíos se reenvían como objeto JSON, no como lista
        $this->assertSame('{}', json_encode($respuesta->contenido['parts'][0]['functionCall']['args']));

        // La key viaja en la cabecera, nunca en la URL
        Http::assertSent(fn (Request $r) => $r->hasHeader('x-goog-api-key', 'KEY-PRUEBA') && ! str_contains($r->url(), 'KEY-PRUEBA'));
    }

    #[Test]
    public function el_cliente_de_gemini_reporta_el_limite_de_uso(): void
    {
        Http::fake(['*generateContent' => Http::response(['error' => ['message' => 'quota']], 429)]);

        $this->expectException(LimiteIAException::class);

        (new GeminiModeloIA('KEY', ['modelo-a']))->responder('x', [], [['role' => 'user', 'parts' => [['text' => 'hola']]]]);
    }
}

/** Modelo de IA guionado para los tests. */
class ModeloSimulado implements ModeloIA
{
    /** @var array<int, RespuestaIA> */
    public array $guion = [];

    /** @var array<int, array<int, array<string, mixed>>> */
    public array $conversaciones = [];

    public bool $disponible = true;

    public ?\Throwable $excepcion = null;

    public function configurado(): bool
    {
        return $this->disponible;
    }

    public function responder(string $instrucciones, array $funciones, array $conversacion): RespuestaIA
    {
        $this->conversaciones[] = $conversacion;

        if ($this->excepcion) {
            throw $this->excepcion;
        }

        return array_shift($this->guion) ?? RespuestaSimulada::texto('');
    }

    /** Resultados de funciones que el asistente le devolvió al modelo. */
    public function resultadosRecibidos(): array
    {
        $resultados = [];

        foreach ($this->conversaciones as $conversacion) {
            foreach ($conversacion as $turno) {
                foreach ($turno['parts'] ?? [] as $parte) {
                    if (isset($parte['functionResponse'])) {
                        $resultados[] = $parte['functionResponse']['response'];
                    }
                }
            }
        }

        return array_values(array_unique($resultados, SORT_REGULAR));
    }
}

class RespuestaSimulada
{
    public static function texto(string $texto): RespuestaIA
    {
        return new RespuestaIA('STOP', $texto, [], ['role' => 'model', 'parts' => [['text' => $texto]]]);
    }

    public static function llamada(string $nombre, array $args): RespuestaIA
    {
        return new RespuestaIA('STOP', '', [['name' => $nombre, 'args' => $args]],
            ['role' => 'model', 'parts' => [['functionCall' => ['name' => $nombre, 'args' => $args]]]]);
    }
}

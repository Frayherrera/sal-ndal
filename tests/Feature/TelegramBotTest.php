<?php

namespace Tests\Feature;

use App\Models\CategoriaGasto;
use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class TelegramBotTest extends TestCase
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
            'services.telegram.user_email' => 'bot@santini.test',
        ]);

        User::factory()->create(['email' => 'bot@santini.test']);

        // Igual que la API real: algunos métodos devuelven "result": true y otros un objeto
        Http::fake([
            'api.telegram.org/*/setWebhook' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/*/setMyCommands' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/*/answerCallbackQuery' => Http::response(['ok' => true, 'result' => true]),
            'api.telegram.org/*' => Http::response(['ok' => true, 'result' => ['message_id' => 99]]),
        ]);
    }

    // ---------------------------------------------------------------- helpers

    private function enviarTexto(string $texto, int $chat = self::CHAT)
    {
        return $this->postJson('/telegram/webhook', [
            'update_id' => $this->updateId++,
            'message' => [
                'message_id' => 10,
                'chat' => ['id' => $chat, 'type' => 'private'],
                'from' => ['id' => $chat, 'first_name' => 'Prueba'],
                'text' => $texto,
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secreto-webhook']);
    }

    private function pulsar(string $data, int $chat = self::CHAT)
    {
        return $this->postJson('/telegram/webhook', [
            'update_id' => $this->updateId++,
            'callback_query' => [
                'id' => 'cb'.$this->updateId,
                'from' => ['id' => $chat],
                'message' => ['message_id' => 99, 'chat' => ['id' => $chat]],
                'data' => $data,
            ],
        ], ['X-Telegram-Bot-Api-Secret-Token' => 'secreto-webhook']);
    }

    /** Texto del último mensaje enviado o editado por el bot. */
    private function ultimaRespuesta(): string
    {
        $peticion = collect(Http::recorded())
            ->map(fn ($par) => $par[0])
            ->filter(fn (Request $r) => str_contains($r->url(), 'sendMessage') || str_contains($r->url(), 'editMessageText'))
            ->last();

        $this->assertNotNull($peticion, 'El bot no respondió nada.');

        return $peticion['text'];
    }

    /** callback_data del botón "Confirmar" del último mensaje con botones. */
    private function tokenConfirmar(): string
    {
        $peticion = collect(Http::recorded())
            ->map(fn ($par) => $par[0])
            ->filter(fn (Request $r) => isset($r['reply_markup']))
            ->last();

        $this->assertNotNull($peticion, 'El bot no pidió confirmación.');

        return $peticion['reply_markup']['inline_keyboard'][0][0]['callback_data'];
    }

    private function cliente(string $nombre, string $local = null): Cliente
    {
        return Cliente::factory()->create(['nombre' => $nombre, 'local' => $local]);
    }

    private function ventaCredito(Cliente $cliente, float $total, string $fecha): Venta
    {
        return app(VentaService::class)->crear([
            'cliente_id' => $cliente->id,
            'fecha' => $fecha,
            'total' => $total,
            'forma_pago' => 'credito',
            'abono_inicial' => 0,
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
        ]);
    }

    // -------------------------------------------------------------- seguridad

    #[Test]
    public function el_webhook_rechaza_llamadas_sin_el_secreto(): void
    {
        $this->postJson('/telegram/webhook', ['update_id' => 1])->assertForbidden();
        $this->postJson('/telegram/webhook', ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => 'otro'])->assertForbidden();

        config(['services.telegram.webhook_secret' => null]);
        $this->postJson('/telegram/webhook', ['update_id' => 1], ['X-Telegram-Bot-Api-Secret-Token' => ''])->assertForbidden();

        Http::assertNothingSent();
    }

    #[Test]
    public function un_chat_no_autorizado_recibe_su_id_y_no_puede_registrar(): void
    {
        $this->enviarTexto('/gasto 70000 comida', 999)->assertOk();

        $this->assertStringContainsString('no está autorizado', $this->ultimaRespuesta());
        $this->assertStringContainsString('999', $this->ultimaRespuesta());
        $this->assertDatabaseCount('gastos', 0);

        // Tampoco puede confirmar con un botón
        $this->pulsar('si:cualquiera', 999)->assertOk();
        $this->assertDatabaseCount('gastos', 0);
    }

    // -------------------------------------------------------------- consultas

    #[Test]
    public function ayuda_y_comandos_desconocidos(): void
    {
        $this->enviarTexto('/start');
        $this->assertStringContainsString('/resumen', $this->ultimaRespuesta());

        $this->enviarTexto('/inventado');
        $this->assertStringContainsString('Comando desconocido', $this->ultimaRespuesta());

        $this->enviarTexto('hola');
        $this->assertStringContainsString('/ayuda', $this->ultimaRespuesta());
    }

    #[Test]
    public function resumen_del_dia_y_del_mes(): void
    {
        $cliente = $this->cliente('TIENDA LA ESQUINA');
        $this->ventaCredito($cliente, 250000, now()->toDateString());
        Gasto::factory()->create(['monto' => 70000]);

        $this->enviarTexto('/resumen@SantiniBot');

        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Vendido: $250.000 (1 ventas)', $texto);
        $this->assertStringContainsString('Gastado: $70.000', $texto);
        $this->assertStringContainsString('Ventas − gastos: <b>$180.000</b>', $texto);
        $this->assertStringContainsString('Saldo pendiente: $250.000', $texto);
    }

    #[Test]
    public function cartera_general_y_de_un_cliente(): void
    {
        $yurani = $this->cliente('YURANI CHINCHILLA', 'POLLO JUANES');
        $this->ventaCredito($yurani, 52000, now()->subDays(2)->toDateString());
        $this->ventaCredito($yurani, 36000, now()->toDateString());
        $this->cliente('CLIENTE AL DÍA');

        $this->enviarTexto('/cartera');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Saldo total: <b>$88.000</b>', $texto);
        $this->assertStringContainsString('YURANI CHINCHILLA: <b>$88.000</b>', $texto);
        $this->assertStringNotContainsString('CLIENTE AL DÍA', $texto);

        // Sin tildes ni mayúsculas, y por el nombre del local
        $this->enviarTexto('/cartera pollo juanes');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Debe: <b>$88.000</b>', $texto);
        $this->assertStringContainsString('VTA-00001', $texto);
        $this->assertStringContainsString('VTA-00002', $texto);

        $this->enviarTexto('/cartera cliente al dia');
        $this->assertStringContainsString('No tiene saldo pendiente', $this->ultimaRespuesta());
    }

    #[Test]
    public function stock_y_alertas(): void
    {
        $comino = MateriaPrima::factory()->create(['nombre' => 'COMINO MOLIDO', 'stock_minimo' => 5, 'activo' => true]);
        InventarioMateriaPrima::create(['materia_prima_id' => $comino->id, 'stock_gramos' => 2500, 'costo_promedio' => 0]);

        $this->enviarTexto('/stock comino');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('COMINO MOLIDO: 2,5 kg ⚠️ bajo', $texto);

        $this->enviarTexto('/stock x');
        $this->assertStringContainsString('Escribe parte del nombre', $this->ultimaRespuesta());

        $this->enviarTexto('/alertas');
        $this->assertStringContainsString('COMINO MOLIDO: 2,5 kg (mín. 5)', $this->ultimaRespuesta());
    }

    #[Test]
    public function ultimas_ventas_y_gastos(): void
    {
        $this->ventaCredito($this->cliente('SAID RUEDA'), 52000, now()->toDateString());
        Gasto::factory()->create(['descripcion' => 'Peaje', 'monto' => 16100, 'numero' => 'GTO-00001']);

        $this->enviarTexto('/ventas');
        $this->assertStringContainsString('SAID RUEDA', $this->ultimaRespuesta());

        $this->enviarTexto('/gastos 5');
        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Peaje', $texto);
        $this->assertStringContainsString('$16.100', $texto);
    }

    // -------------------------------------------------------------- registros

    #[Test]
    public function registrar_un_gasto_pide_confirmacion_y_lo_guarda_al_confirmar(): void
    {
        $ruta = CategoriaGasto::factory()->create(['nombre' => 'Ruta']);

        $this->enviarTexto('/gasto 70.000 comida | ruta');

        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Valor: <b>$70.000</b>', $texto);
        $this->assertStringContainsString('Descripción: Comida', $texto);
        $this->assertStringContainsString('Categoría: Ruta', $texto);
        $this->assertDatabaseCount('gastos', 0); // todavía no se guarda

        $boton = $this->tokenConfirmar();
        $this->pulsar($boton)->assertOk();

        $gasto = Gasto::firstOrFail();
        $this->assertSame(70000.0, (float) $gasto->monto);
        $this->assertSame('Comida', $gasto->descripcion);
        $this->assertSame($ruta->id, $gasto->categoria_gasto_id);
        $this->assertSame(User::where('email', 'bot@santini.test')->value('id'), $gasto->user_id);
        $this->assertStringContainsString('Gasto <code>GTO-00001</code> registrado', $this->ultimaRespuesta());

        // El mismo botón no registra dos veces
        $this->pulsar($boton)->assertOk();
        $this->assertDatabaseCount('gastos', 1);
        $this->assertStringContainsString('Solicitud vencida', $this->ultimaRespuesta());
    }

    #[Test]
    public function cancelar_un_registro_no_guarda_nada(): void
    {
        $this->enviarTexto('/gasto 16100 peaje');
        $token = substr($this->tokenConfirmar(), 3);

        $this->pulsar('no:'.$token);

        $this->assertStringContainsString('cancelado', $this->ultimaRespuesta());
        $this->assertDatabaseCount('gastos', 0);
    }

    #[Test]
    public function el_gasto_sin_categoria_va_a_otros_y_valida_el_valor(): void
    {
        $this->enviarTexto('/gasto 70k almuerzo');
        $this->assertStringContainsString('Categoría: Otros', $this->ultimaRespuesta());
        $this->assertStringContainsString('$70.000', $this->ultimaRespuesta());

        $this->enviarTexto('/gasto comida');
        $this->assertStringContainsString('No entendí el valor del gasto', $this->ultimaRespuesta());

        $this->enviarTexto('/gasto 5000 algo | categoria inexistente');
        $this->assertStringContainsString('No encontré ninguna categoría', $this->ultimaRespuesta());
    }

    #[Test]
    public function registrar_una_venta_de_contado_y_a_credito(): void
    {
        $jesus = $this->cliente('JESUS DUQUE', 'AUTOSERVICIO EL PAISA');

        $this->enviarTexto('/venta jesús duque 230000');
        $this->assertStringContainsString('Contado', $this->ultimaRespuesta());
        $this->pulsar($this->tokenConfirmar());

        $this->enviarTexto('/venta jesus 100.000 credito | pedido de canela');
        $this->assertStringContainsString('Crédito', $this->ultimaRespuesta());
        $this->pulsar($this->tokenConfirmar());

        $ventas = Venta::where('cliente_id', $jesus->id)->orderBy('id')->get();

        $this->assertCount(2, $ventas);
        $this->assertSame('contado', $ventas[0]->forma_pago);
        $this->assertSame('pagada', $ventas[0]->estadoPago());
        $this->assertSame('credito', $ventas[1]->forma_pago);
        $this->assertSame(100000.0, $ventas[1]->saldo());
        $this->assertSame('pedido de canela', $ventas[1]->observaciones);
        $this->assertNotNull($ventas[1]->fecha_vencimiento);
    }

    #[Test]
    public function la_venta_pide_precisar_si_el_cliente_es_ambiguo(): void
    {
        $this->cliente('RAMON PACHECO');
        $this->cliente('MILADYS PACHECO');

        $this->enviarTexto('/venta pacheco 50000');

        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Hay varios clientes', $texto);
        $this->assertStringContainsString('RAMON PACHECO', $texto);
        $this->assertStringContainsString('MILADYS PACHECO', $texto);

        $this->enviarTexto('/venta nadie 50000');
        $this->assertStringContainsString('No encontré ningún cliente', $this->ultimaRespuesta());
        $this->assertDatabaseCount('ventas', 0);
    }

    #[Test]
    public function el_abono_se_reparte_entre_las_ventas_mas_antiguas(): void
    {
        $yurani = $this->cliente('YURANI CHINCHILLA');
        $vieja = $this->ventaCredito($yurani, 52000, now()->subDays(5)->toDateString());
        $nueva = $this->ventaCredito($yurani, 36000, now()->toDateString());

        $this->enviarTexto('/abono yurani 60000 nequi');

        $texto = $this->ultimaRespuesta();
        $this->assertStringContainsString('Nequi', $texto);
        $this->assertStringContainsString($vieja->numero, $texto);
        $this->assertStringContainsString($nueva->numero, $texto);

        $this->pulsar($this->tokenConfirmar());

        $this->assertSame(0.0, $vieja->fresh()->saldo());
        $this->assertSame(28000.0, $nueva->fresh()->saldo());
        $this->assertSame('nequi', $nueva->pagos()->first()->metodo);
        $this->assertStringContainsString('Saldo pendiente del cliente: <b>$28.000</b>', $this->ultimaRespuesta());
    }

    #[Test]
    public function no_acepta_abonos_mayores_a_lo_que_debe_el_cliente(): void
    {
        $said = $this->cliente('SAID RUEDA');
        $this->ventaCredito($said, 52000, now()->toDateString());

        $this->enviarTexto('/abono said rueda 90000');

        $this->assertStringContainsString('supera lo que debe', $this->ultimaRespuesta());
        $this->assertDatabaseCount('pago_venta', 0);

        $this->enviarTexto('/abono '.$this->cliente('SIN DEUDA')->nombre.' 1000');
        $this->assertStringContainsString('no tiene saldo pendiente', $this->ultimaRespuesta());
    }

    #[Test]
    public function el_registro_se_revalida_al_confirmar(): void
    {
        $said = $this->cliente('SAID RUEDA');
        $venta = $this->ventaCredito($said, 52000, now()->toDateString());

        $this->enviarTexto('/abono said 52000');
        $boton = $this->tokenConfirmar();

        // Mientras tanto, alguien registró el pago desde la app
        $venta->pagos()->create(['fecha' => now(), 'monto' => 52000, 'metodo' => 'efectivo']);

        $this->pulsar($boton);

        $this->assertStringContainsString('No se pudo registrar', $this->ultimaRespuesta());
        $this->assertSame(1, $venta->pagos()->count());
    }

    #[Test]
    public function interpreta_los_valores_escritos_de_distintas_formas(): void
    {
        $casos = [
            '70000' => 70000.0, '70.000' => 70000.0, '70,000' => 70000.0, '$70.000' => 70000.0,
            '1.250.000' => 1250000.0, '70k' => 70000.0, '1,5m' => 1500000.0, '129727' => 129727.0,
            '16100' => 16100.0, '2.5' => 2.5,
        ];

        foreach ($casos as $texto => $esperado) {
            $this->assertSame($esperado, \App\Services\Telegram\TelegramFormato::monto($texto), $texto);
        }

        foreach (['', 'abc', '0', '-500', '12a'] as $invalido) {
            $this->assertNull(\App\Services\Telegram\TelegramFormato::monto($invalido), $invalido);
        }
    }

    #[Test]
    public function el_comando_de_webhook_exige_https_y_secreto(): void
    {
        config(['app.url' => 'http://localhost:8080']);

        $this->artisan('telegram:webhook set')->assertFailed();
        $this->artisan('telegram:webhook set --url=https://santini.ejemplo.com')->assertSuccessful();

        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'setWebhook')
            && $r['url'] === 'https://santini.ejemplo.com/telegram/webhook'
            && $r['secret_token'] === 'secreto-webhook');
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'setMyCommands'));

        config(['services.telegram.webhook_secret' => null]);
        $this->artisan('telegram:webhook set --url=https://santini.ejemplo.com')->assertFailed();
    }
}

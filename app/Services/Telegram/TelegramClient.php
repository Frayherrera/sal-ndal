<?php

namespace App\Services\Telegram;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Cliente mínimo de la Bot API de Telegram (https://core.telegram.org/bots/api).
 */
class TelegramClient
{
    public function __construct(private ?string $token = null)
    {
        $this->token ??= config('services.telegram.token');
    }

    public function configurado(): bool
    {
        return filled($this->token);
    }

    /**
     * @param  array<string, mixed>  $extra  reply_markup, etc.
     * @return array<string, mixed>
     */
    public function enviarMensaje(int|string $chatId, string $texto, array $extra = []): array
    {
        return $this->llamar('sendMessage', [
            'chat_id' => $chatId,
            'text' => $texto,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ] + $extra);
    }

    /**
     * @param  array<string, mixed>  $extra
     * @return array<string, mixed>
     */
    public function editarMensaje(int|string $chatId, int $messageId, string $texto, array $extra = []): array
    {
        return $this->llamar('editMessageText', [
            'chat_id' => $chatId,
            'message_id' => $messageId,
            'text' => $texto,
            'parse_mode' => 'HTML',
            'disable_web_page_preview' => true,
        ] + $extra);
    }

    /**
     * @return array<string, mixed>
     */
    public function responderCallback(string $callbackId, ?string $texto = null): array
    {
        return $this->llamar('answerCallbackQuery', array_filter([
            'callback_query_id' => $callbackId,
            'text' => $texto,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function configurarWebhook(string $url, ?string $secreto): array
    {
        return $this->llamar('setWebhook', array_filter([
            'url' => $url,
            'secret_token' => $secreto,
            'allowed_updates' => ['message', 'callback_query'],
            'drop_pending_updates' => true,
        ]));
    }

    /**
     * @return array<string, mixed>
     */
    public function eliminarWebhook(): array
    {
        return $this->llamar('deleteWebhook', ['drop_pending_updates' => true]);
    }

    /**
     * @return array<string, mixed>
     */
    public function infoWebhook(): array
    {
        return $this->llamar('getWebhookInfo');
    }

    /**
     * @return array<string, mixed>
     */
    public function infoBot(): array
    {
        return $this->llamar('getMe');
    }

    /**
     * Publica el menú de comandos que Telegram muestra al escribir "/".
     *
     * @param  array<string, string>  $comandos  comando => descripción
     * @return array<string, mixed>
     */
    public function configurarComandos(array $comandos): array
    {
        return $this->llamar('setMyCommands', [
            'commands' => collect($comandos)
                ->map(fn (string $descripcion, string $comando) => ['command' => $comando, 'description' => $descripcion])
                ->values()
                ->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $parametros
     * @return array<string, mixed>
     */
    private function llamar(string $metodo, array $parametros = []): array
    {
        if (! $this->configurado()) {
            throw new RuntimeException('Falta TELEGRAM_BOT_TOKEN en el .env.');
        }

        try {
            $respuesta = Http::timeout(15)
                ->asJson()
                ->post("https://api.telegram.org/bot{$this->token}/{$metodo}", $parametros);
        } catch (ConnectionException) {
            // El mensaje original incluye la URL con el token: no se propaga
            throw new RuntimeException("Telegram {$metodo}: no se pudo conectar con api.telegram.org.");
        }

        $cuerpo = $respuesta->json() ?? [];

        if (! $respuesta->successful() || ! ($cuerpo['ok'] ?? false)) {
            throw new RuntimeException("Telegram {$metodo}: ".($cuerpo['description'] ?? 'HTTP '.$respuesta->status()));
        }

        // Algunos métodos (setWebhook, deleteWebhook, setMyCommands, answerCallbackQuery)
        // devuelven "result": true en lugar de un objeto
        $resultado = $cuerpo['result'] ?? [];

        return is_array($resultado) ? $resultado : ['resultado' => $resultado];
    }
}

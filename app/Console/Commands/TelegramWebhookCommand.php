<?php

namespace App\Console\Commands;

use App\Services\Telegram\TelegramBot;
use App\Services\Telegram\TelegramClient;
use Illuminate\Console\Command;
use RuntimeException;

/**
 * Administra el webhook del bot de Telegram.
 *
 *   php artisan telegram:webhook info
 *   php artisan telegram:webhook set                      (usa APP_URL)
 *   php artisan telegram:webhook set --url=https://xxxx.trycloudflare.com
 *   php artisan telegram:webhook delete
 */
class TelegramWebhookCommand extends Command
{
    protected $signature = 'telegram:webhook
        {accion=info : info | set | delete}
        {--url= : URL pública HTTPS base de la app (por defecto APP_URL)}';

    protected $description = 'Registra, consulta o elimina el webhook del bot de Telegram';

    public function handle(TelegramClient $telegram): int
    {
        try {
            return match ($this->argument('accion')) {
                'info' => $this->mostrarInfo($telegram),
                'set' => $this->set($telegram),
                'delete' => $this->delete($telegram),
                default => $this->fallar('Acción no válida. Usa: info, set o delete.'),
            };
        } catch (RuntimeException $e) {
            return $this->fallar($e->getMessage());
        }
    }

    private function mostrarInfo(TelegramClient $telegram): int
    {
        $bot = $telegram->infoBot();
        $webhook = $telegram->infoWebhook();

        $this->line('Bot: @'.($bot['username'] ?? '?').' ('.($bot['first_name'] ?? '').')');
        $this->line('Webhook: '.(($webhook['url'] ?? '') ?: '(sin configurar)'));
        $this->line('Updates pendientes: '.($webhook['pending_update_count'] ?? 0));

        if (! empty($webhook['last_error_message'])) {
            $this->warn('Último error: '.$webhook['last_error_message']
                .(isset($webhook['last_error_date']) ? ' ('.date('d/m/Y H:i', $webhook['last_error_date']).')' : ''));
        }

        $chats = config('services.telegram.allowed_chat_ids');
        $this->line('Chats autorizados: '.($chats ? implode(', ', $chats) : '(ninguno — el bot no atenderá a nadie)'));

        return self::SUCCESS;
    }

    private function set(TelegramClient $telegram): int
    {
        $secreto = config('services.telegram.webhook_secret');

        if (blank($secreto)) {
            return $this->fallar('Define TELEGRAM_WEBHOOK_SECRET en el .env (un texto aleatorio de 1 a 256 caracteres: letras, números, _ y -).');
        }

        $base = rtrim((string) ($this->option('url') ?: config('app.url')), '/');

        if (! str_starts_with($base, 'https://')) {
            return $this->fallar("Telegram exige una URL pública HTTPS y la actual es «{$base}». Usa --url=https://...");
        }

        $url = $base.'/telegram/webhook';

        $telegram->configurarWebhook($url, $secreto);
        $telegram->configurarComandos(TelegramBot::COMANDOS);

        $this->info("Webhook registrado: {$url}");
        $this->line('Menú de comandos publicado. Escríbele /ayuda al bot para probarlo.');

        return self::SUCCESS;
    }

    private function delete(TelegramClient $telegram): int
    {
        $telegram->eliminarWebhook();
        $this->info('Webhook eliminado. El bot deja de recibir mensajes.');

        return self::SUCCESS;
    }

    private function fallar(string $mensaje): int
    {
        $this->error($mensaje);

        return self::FAILURE;
    }
}

<?php

namespace App\Http\Controllers;

use App\Services\Telegram\TelegramBot;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Recibe los updates del bot de Telegram (POST /telegram/webhook).
 *
 * Telegram firma cada llamada con la cabecera X-Telegram-Bot-Api-Secret-Token
 * (el secreto que se registra con `php artisan telegram:webhook set`). Sin ese
 * secreto configurado, el webhook rechaza todo.
 */
class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, TelegramBot $bot): JsonResponse
    {
        $secreto = (string) config('services.telegram.webhook_secret');

        if ($secreto === '' || ! hash_equals($secreto, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['ok' => false], 403);
        }

        try {
            $bot->procesar($request->all());
        } catch (Throwable $e) {
            // Se responde 200 igual: si no, Telegram reintenta el mismo update una y otra vez
            Log::error('Telegram: error en el webhook', ['error' => $e->getMessage()]);
        }

        return response()->json(['ok' => true]);
    }
}

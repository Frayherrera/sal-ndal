<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | Bot de Telegram (consultas y registros). Ver la sección "Bot de Telegram" en AGENTS.md.
    | - token: lo entrega @BotFather.
    | - webhook_secret: texto aleatorio; Telegram lo envía en la cabecera
    |   X-Telegram-Bot-Api-Secret-Token y el webhook rechaza lo que no lo traiga.
    | - allowed_chat_ids: IDs de chat autorizados, separados por coma.
    | - user_email: usuario del sistema a cuyo nombre quedan los registros del bot.
    */
    'telegram' => [
        'token' => env('TELEGRAM_BOT_TOKEN'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET'),
        'allowed_chat_ids' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('TELEGRAM_ALLOWED_CHAT_IDS', ''))
        ))),
        'user_email' => env('TELEGRAM_USER_EMAIL', env('ADMIN_EMAIL', 'admin@admin.co')),
    ],

    /*
    | Gemini (Google) para el asistente con IA del bot. Sin key, el bot funciona solo con comandos.
    */
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
        // Se prueban en orden: si uno está saturado (503) o retirado (404), se usa el siguiente
        'modelos' => array_values(array_filter(array_map('trim', explode(',', (string) env(
            'GEMINI_MODELOS',
            'gemini-3.1-flash-lite,gemini-3.5-flash-lite,gemini-3.5-flash'
        ))))),
    ],

];

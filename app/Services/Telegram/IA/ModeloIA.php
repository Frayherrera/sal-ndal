<?php

namespace App\Services\Telegram\IA;

/**
 * Modelo de lenguaje del asistente del bot. La implementación real es GeminiModeloIA;
 * en los tests se reemplaza por una simulada.
 *
 * La conversación usa el formato de la API de Gemini:
 * [['role' => 'user'|'model', 'parts' => [['text' => …] | ['functionCall' => …] | ['functionResponse' => …]]], …]
 */
interface ModeloIA
{
    public function configurado(): bool;

    /**
     * @param  array<int, array<string, mixed>>  $funciones  declaraciones de funciones (name, description, parameters)
     * @param  array<int, array<string, mixed>>  $conversacion
     */
    public function responder(string $instrucciones, array $funciones, array $conversacion): RespuestaIA;
}

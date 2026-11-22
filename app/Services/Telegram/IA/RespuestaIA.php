<?php

namespace App\Services\Telegram\IA;

/**
 * Respuesta del modelo, ya interpretada.
 */
final class RespuestaIA
{
    /**
     * @param  string  $motivoFin  finishReason del modelo (STOP, MAX_TOKENS, SAFETY…)
     * @param  string  $texto  texto de la respuesta (partes de texto unidas)
     * @param  array<int, array{name: string, args: array<string, mixed>}>  $llamadas  funciones que el modelo pide ejecutar
     * @param  array<string, mixed>  $contenido  turno del modelo tal como llegó, para devolverlo sin cambios
     *                                           en la siguiente petición (conserva las firmas de razonamiento)
     */
    public function __construct(
        public readonly string $motivoFin,
        public readonly string $texto,
        public readonly array $llamadas,
        public readonly array $contenido,
    ) {}
}

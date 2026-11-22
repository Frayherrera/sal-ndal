<?php

namespace App\Services\Telegram\IA;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Gemini (Google) vía la API REST generateContent, con function calling.
 * https://ai.google.dev/api/generate-content
 *
 * Prueba los modelos configurados en orden: si uno está saturado (503) o ya no
 * está disponible (404), pasa al siguiente.
 */
class GeminiModeloIA implements ModeloIA
{
    private const URL = 'https://generativelanguage.googleapis.com/v1beta/models/%s:generateContent';

    /**
     * @param  array<int, string>|null  $modelos
     */
    public function __construct(
        private ?string $key = null,
        private ?array $modelos = null,
    ) {
        $this->key ??= config('services.gemini.key');
        $this->modelos ??= config('services.gemini.modelos', []);
    }

    public function configurado(): bool
    {
        return filled($this->key) && $this->modelos !== [];
    }

    public function responder(string $instrucciones, array $funciones, array $conversacion): RespuestaIA
    {
        if (! $this->configurado()) {
            throw new RuntimeException('Falta GEMINI_API_KEY en el .env.');
        }

        $cuerpo = [
            'systemInstruction' => ['parts' => [['text' => $instrucciones]]],
            'contents' => $conversacion,
            'tools' => [['functionDeclarations' => $funciones]],
            'generationConfig' => ['temperature' => 0.2],
        ];

        $ultimoError = 'sin modelos configurados';

        foreach ($this->modelos as $modelo) {
            $respuesta = $this->llamar($modelo, $cuerpo);

            if ($respuesta->successful()) {
                return $this->interpretar($respuesta);
            }

            if ($respuesta->status() === 429) {
                throw new LimiteIAException('Se alcanzó el límite de uso gratuito de Gemini por ahora.');
            }

            $ultimoError = $respuesta->status().': '.($respuesta->json('error.message') ?? 'error desconocido');

            // Saturado o retirado: se intenta con el siguiente modelo. Otro error: se detiene.
            if (! in_array($respuesta->status(), [404, 500, 503], true)) {
                break;
            }
        }

        throw new RuntimeException('Gemini respondió '.$ultimoError);
    }

    /**
     * @param  array<string, mixed>  $cuerpo
     */
    private function llamar(string $modelo, array $cuerpo): Response
    {
        try {
            // La key va en la cabecera (no en la URL) para que no quede en logs ni mensajes de error
            return Http::timeout(30)
                ->withHeaders(['x-goog-api-key' => $this->key])
                ->asJson()
                ->post(sprintf(self::URL, $modelo), $cuerpo);
        } catch (ConnectionException) {
            throw new RuntimeException('No se pudo conectar con Gemini.');
        }
    }

    private function interpretar(Response $respuesta): RespuestaIA
    {
        $candidato = $respuesta->json('candidates.0') ?? [];
        $contenido = $candidato['content'] ?? ['role' => 'model', 'parts' => []];
        $contenido['role'] ??= 'model';

        $texto = '';
        $llamadas = [];

        foreach ($contenido['parts'] ?? [] as $i => $parte) {
            if (isset($parte['functionCall']['name'])) {
                $llamadas[] = [
                    'name' => $parte['functionCall']['name'],
                    'args' => (array) ($parte['functionCall']['args'] ?? []),
                ];

                // Al decodificar, un objeto vacío {} queda como [] y se reenviaría como lista
                if (empty($parte['functionCall']['args'])) {
                    $contenido['parts'][$i]['functionCall']['args'] = new \stdClass;
                }
            } elseif (isset($parte['text']) && empty($parte['thought'])) {
                $texto .= $parte['text'];
            }
        }

        return new RespuestaIA(
            motivoFin: (string) ($candidato['finishReason'] ?? ($respuesta->json('promptFeedback.blockReason') ?? 'DESCONOCIDO')),
            texto: trim($texto),
            llamadas: $llamadas,
            contenido: $contenido,
        );
    }
}

<?php

namespace App\Services\Telegram;

use Illuminate\Support\Str;

/**
 * Utilidades de texto para los mensajes del bot (HTML de Telegram).
 */
class TelegramFormato
{
    public static function dinero(float|int|string|null $valor): string
    {
        $valor = (float) $valor;

        return ($valor < 0 ? '−$' : '$').number_format(abs($valor), 0, ',', '.');
    }

    /** Escapa texto del usuario o de la BD para parse_mode HTML. */
    public static function e(?string $texto): string
    {
        return htmlspecialchars((string) $texto, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    /** Minúsculas, sin tildes y con espacios simples: para comparar nombres. */
    public static function normalizar(string $texto): string
    {
        return Str::squish(Str::lower(Str::ascii($texto)));
    }

    /**
     * Interpreta un valor en pesos escrito de forma natural:
     * 70000 · 70.000 · 70,000 · $70.000 · 70k · 1,5m · 129727
     * Devuelve null si no es un monto válido.
     */
    public static function monto(string $texto): ?float
    {
        $t = Str::lower(str_replace(['$', ' '], '', trim($texto)));

        $multiplicador = 1;

        if (preg_match('/^(.+?)(k|mil|m|millon|millones)$/', $t, $m)) {
            $t = $m[1];
            $multiplicador = in_array($m[2], ['k', 'mil'], true) ? 1000 : 1000000;
        }

        if ($t === '') {
            return null;
        }

        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $t)) {
            // Separadores de miles: 70.000 · 1.250.000 · 70,000
            $numero = (float) str_replace(['.', ','], '', $t);
        } elseif (preg_match('/^\d+([.,]\d+)?$/', $t)) {
            // Entero o decimal simple: 70000 · 1,5 · 2.5
            $numero = (float) str_replace(',', '.', $t);
        } else {
            return null;
        }

        $valor = round($numero * $multiplicador, 2);

        return $valor > 0 ? $valor : null;
    }

    /**
     * Recorta un mensaje al máximo que acepta Telegram (4096 caracteres).
     * Corta en un salto de línea para no dejar etiquetas HTML abiertas.
     */
    public static function limitar(string $texto, int $max = 4000): string
    {
        if (mb_strlen($texto) <= $max) {
            return $texto;
        }

        $corte = mb_substr($texto, 0, $max);
        $ultimoSalto = mb_strrpos($corte, "\n");

        return ($ultimoSalto !== false ? mb_substr($corte, 0, $ultimoSalto) : $corte)."\n…";
    }
}

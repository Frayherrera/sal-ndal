<?php

namespace App\Support;

use App\Models\ProductoTerminado;

/**
 * Conversión entre paquetes (como se cuenta en bodega y en el camión) y
 * unidades (como se guarda el inventario de producto terminado).
 */
final class Paquetes
{
    public const UNIDADES = ProductoTerminado::UNIDADES_POR_PAQUETE;

    /** Paquetes + unidades sueltas → unidades. */
    public static function aUnidades(int|float|string|null $paquetes, int|float|string|null $sueltas = 0): int
    {
        return (int) round((float) ($paquetes ?: 0) * self::UNIDADES) + (int) ($sueltas ?: 0);
    }

    /**
     * Unidades → [paquetes completos, unidades sueltas].
     *
     * @return array{0: int, 1: int}
     */
    public static function separar(int $unidades): array
    {
        return [intdiv($unidades, self::UNIDADES), $unidades % self::UNIDADES];
    }

    /** 245 → "12 p + 5 u" · 240 → "12 p" · 5 → "5 u" · 0 → "0". */
    public static function texto(?int $unidades): string
    {
        $unidades = (int) $unidades;

        if ($unidades === 0) {
            return '0';
        }

        $signo = $unidades < 0 ? '−' : '';
        [$p, $u] = self::separar(abs($unidades));

        return $signo.implode(' + ', array_filter([
            $p > 0 ? number_format($p, 0, ',', '.').' p' : null,
            $u > 0 ? $u.' u' : null,
        ]));
    }
}

<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Agrupación por día (o por semana en periodos largos) para los gráficos de los
 * reportes PDF. Se hace en PHP para no depender del motor de base de datos.
 */
trait AgrupaPorPeriodo
{
    /** A partir de cuántos días se agrupa por semana en lugar de por día. */
    protected int $diasMaxPorDia = 31;

    /** Hasta cuántos días se muestran también los días sin movimiento (más allá ocupan demasiado). */
    protected int $diasMaxConVacios = 14;

    /**
     * Periodo del reporte: el de los filtros o, si no hay, el de los registros encontrados.
     *
     * @param  Collection<int, mixed>  $registros
     * @param  array{desde?: ?string, hasta?: ?string}  $filtros
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    protected function periodoDe(Collection $registros, callable $fecha, array $filtros): array
    {
        $fechas = $registros->map(fn ($r) => $fecha($r))->filter();

        $desde = filled($filtros['desde'] ?? null) ? Carbon::parse($filtros['desde']) : $fechas->min();
        $hasta = filled($filtros['hasta'] ?? null) ? Carbon::parse($filtros['hasta']) : $fechas->max();

        $desde = $desde ? Carbon::parse($desde)->startOfDay() : null;
        $hasta = $hasta ? Carbon::parse($hasta)->startOfDay() : null;

        if ($desde && $hasta && $desde->gt($hasta)) {
            [$desde, $hasta] = [$hasta, $desde];
        }

        return [$desde, $hasta];
    }

    /**
     * Suma por día o por semana. Hasta 14 días incluye los días sin registros; de 15 a 31
     * solo los días con movimiento; más de 31 agrupa por semana (con las semanas vacías).
     *
     * @param  Collection<int, mixed>  $registros
     * @param  callable(mixed): Carbon  $fecha
     * @param  callable(mixed): float  $valor
     * @return array{agrupacion: string, filas: array<int, array{etiqueta: string, total: float, count: int}>, maximo: float}
     */
    protected function agruparPorPeriodo(Collection $registros, callable $fecha, callable $valor, ?Carbon $desde, ?Carbon $hasta): array
    {
        if (! $desde || ! $hasta) {
            return ['agrupacion' => 'dia', 'filas' => [], 'maximo' => 0.0];
        }

        $dias = $desde->diffInDays($hasta) + 1;
        $porSemana = $dias > $this->diasMaxPorDia;
        $omitirVacios = ! $porSemana && $dias > $this->diasMaxConVacios;
        $clave = fn (Carbon $f) => $porSemana ? $f->copy()->startOfWeek()->toDateString() : $f->toDateString();
        $agrupados = $registros->groupBy(fn ($r) => $clave(Carbon::parse($fecha($r))));

        $filas = [];
        $cursor = $porSemana ? $desde->copy()->startOfWeek() : $desde->copy()->startOfDay();

        while ($cursor->lte($hasta)) {
            $grupo = $agrupados->get($cursor->toDateString(), collect());

            if ($omitirVacios && $grupo->isEmpty()) {
                $cursor->addDay();

                continue;
            }

            $filas[] = [
                'etiqueta' => $porSemana
                    ? 'Sem. '.$cursor->format('d/m')
                    : ucfirst($cursor->locale('es')->translatedFormat('D')).' '.$cursor->format('d/m'),
                'total' => round((float) $grupo->sum(fn ($r) => $valor($r)), 2),
                'count' => $grupo->count(),
            ];
            $porSemana ? $cursor->addWeek() : $cursor->addDay();
        }

        return [
            'agrupacion' => $porSemana ? 'semana' : 'dia',
            'filas' => $filas,
            'maximo' => (float) max(array_column($filas, 'total') ?: [0]),
        ];
    }
}

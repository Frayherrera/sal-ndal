<?php

namespace Database\Seeders;

/**
 * Gastos de la ruta del 3 al 7 de noviembre de 2026, todos en la categoría "Ruta".
 *
 *   php artisan db:seed --class=GastoRutaSeeder
 */
class GastoRutaSeeder extends SeederGastosDeCategoria
{
    protected function categoria(): string
    {
        return 'Ruta';
    }

    protected function descripcionCategoria(): ?string
    {
        return 'Gastos de viaje en ruta: comida, hotel, combustible, peajes.';
    }

    protected function gastos(): array
    {
        return [
            ['2026-11-03', 'Comida', 70000],
            ['2026-11-04', 'Comida', 70000],
            ['2026-11-04', 'Lapicero', 1000],
            ['2026-11-05', 'A.C.P.M.', 129727],
            ['2026-11-05', 'Peaje', 16100],
            ['2026-11-05', 'Comida', 70000],
            ['2026-11-05', 'Hotel', 50000],
            ['2026-11-06', 'Hotel', 50000],
            ['2026-11-06', 'Comida', 70000],
            ['2026-11-07', 'Peaje', 16100],
            ['2026-11-07', 'Comida', 70000],
        ];
    }
}

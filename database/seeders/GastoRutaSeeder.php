<?php

namespace Database\Seeders;

/**
 * Gastos de la ruta del 17 al 20 de noviembre de 2026, todos en la categoría "Ruta".
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
            ['2026-11-17', 'A.C.P.M.', 50000],
            ['2026-11-17', 'Revisada de llanta', 15000],
            ['2026-11-17', 'Ferri Gamarra', 27000],
            ['2026-11-17', 'Ferri Arenal', 30000],
            ['2026-11-17', 'Comida', 50000],
            ['2026-11-17', 'Hotel', 50000],
            ['2026-11-17', 'Parqueo', 10000],
            ['2026-11-17', 'Talonario', 4500],
            ['2026-11-18', 'Ayudante', 100000],
            ['2026-11-18', 'Comida', 35000],
            ['2026-11-18', 'Ferri Arenal', 30000],
            ['2026-11-18', 'Hotel', 50000],
            ['2026-11-18', 'Parqueo', 10000],
            ['2026-11-19', 'Comida', 70000],
            ['2026-11-19', 'Ferri Piedra Candela', 40000],
            ['2026-11-19', 'Hotel', 60000],
            ['2026-11-19', 'Parqueo', 10000],
            ['2026-11-20', 'A.C.P.M.', 120000],
            ['2026-11-20', 'Ferri Piedra Candela', 40000],
            ['2026-11-20', 'Ferri Gamarra', 27000],
            ['2026-11-20', 'Comida', 70000],
        ];
    }
}

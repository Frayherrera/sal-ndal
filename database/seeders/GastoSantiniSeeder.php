<?php

namespace Database\Seeders;

/**
 * Gastos de Santini del 8 al 11 de noviembre de 2026 (quincenas, insumos y envíos),
 * todos en la categoría "Santini".
 *
 *   php artisan db:seed --class=GastoSantiniSeeder
 */
class GastoSantiniSeeder extends SeederGastosDeCategoria
{
    protected function categoria(): string
    {
        return 'Santini';
    }

    protected function descripcionCategoria(): ?string
    {
        return 'Gastos generales de Santini: quincenas, insumos y envíos.';
    }

    protected function gastos(): array
    {
        return [
            ['2026-11-08', 'Pago Marcela quincena', 500000],
            ['2026-11-08', 'Pago quincena de Yorgelis', 500000],
            ['2026-11-09', 'Linaza, miel, manzanilla', 633000],
            ['2026-11-10', 'Cereales', 380200],
            ['2026-11-10', 'Chía', 240000],
            ['2026-11-10', 'Envío chía', 13000],
            ['2026-11-10', 'Envío cereales', 40000],
            ['2026-11-10', 'Envío linaza, miel, manzanilla', 38000],
            ['2026-11-10', 'Envío canela', 40000],
            ['2026-11-11', 'Compra de linaza, azúcar de leche y sal mora azul', 281000],
        ];
    }
}

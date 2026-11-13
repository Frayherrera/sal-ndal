<?php

namespace Database\Seeders;

use App\Models\Cliente;
use App\Models\User;
use App\Models\Venta;
use App\Services\VentaService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Ventas de contado del 3 al 7 de noviembre de 2026 a clientes existentes.
 *
 * - Busca cada cliente por nombre (sin distinguir mayúsculas ni espacios extra).
 *   Si el cliente no existe, la venta se omite y se informa.
 * - Es idempotente: una venta del mismo cliente, fecha y total no se vuelve a crear.
 *
 *   php artisan db:seed --class=VentaNoviembreSeeder
 */
class VentaNoviembreSeeder extends Seeder
{
    /** @var array<int, array{0: string, 1: string, 2: float|int}> fecha (Y-m-d), cliente, total */
    public const VENTAS = [
        ['2026-11-03', 'JESUS DUQUE', 230000],
        ['2026-11-03', 'MARIELA BUSTAMANTE', 72000],
        ['2026-11-03', 'SAID RUEDA', 52000],
        ['2026-11-03', 'ARLEY SANCHEZ', 79200],
        ['2026-11-03', 'HERMIDEZ', 17650],
        ['2026-11-03', 'ESTEFANER BALLONA', 157900],
        ['2026-11-03', 'ALEXANDER LEON', 40500],
        ['2026-11-03', 'EDITD BONETH', 19500],
        ['2026-11-03', 'AMALIA QUINTERO', 120900],
        ['2026-11-03', 'SYNDI HERRERA', 170272],
        ['2026-11-04', 'KELLY JHOANA CRUZ', 53500],
        ['2026-11-04', 'MERCADITO GUAJIRO', 40800],
        ['2026-11-04', 'DEIMER ANGARITA', 121800],
        ['2026-11-04', 'RAMON PACHECO', 216800],
        ['2026-11-04', 'AIDYS QUINTERO', 98500],
        ['2026-11-04', 'TIENDA DONDE BETO', 56000],
        ['2026-11-05', 'YURANI CHINCHILLA', 52000],
        ['2026-11-05', 'JHON GARCIA', 152000],
        ['2026-11-05', 'LUZ MARINA LEON', 112500],
        ['2026-11-05', 'YANETH ARDILA', 175900],
        ['2026-11-05', 'RODRIGO OTALVARES', 163500],
        ['2026-11-05', 'EDY NAVARRO', 98500],
        ['2026-11-05', 'MILADYS PACHECO', 19500],
        ['2026-11-05', 'DIEGO DUQUE', 99900],
        ['2026-11-06', 'DANIEL VILLAZON', 71500],
        ['2026-11-06', 'ESTELA AVEDAÑO', 13000],
        ['2026-11-06', 'KAREN TAMAYO', 192000],
        ['2026-11-06', 'SRIVEN CABALLERO', 1062000],
        ['2026-11-06', 'LORENA MORALES', 83000],
        ['2026-11-06', 'JHON CAÑIZARES', 152500],
        ['2026-11-06', 'EVERIS LOPEZ', 42000],
        ['2026-11-06', 'ARACELIS MAJICA', 46500],
        ['2026-11-07', 'OSNEIDER SANGUINO', 82000],
        ['2026-11-07', 'INGRI BELEÑO', 13000],
        ['2026-11-07', 'ESNEIDER TELLEZ', 671800],
        ['2026-11-03', 'OVELMAR ANTONIO', 159996],
        ['2026-11-04', 'IVAN CARDENAS', 12000],
        ['2026-11-04', 'RAMON PACHECO', 72414],
        ['2026-11-05', 'YURANI CHINCHILLA', 36000],
        ['2026-11-06', 'PROVEDOR PAISA', 121492],
        ['2026-11-06', 'PROVEDOR PAISA', 176232],
        ['2026-11-07', 'ESNEIDER TELLEZ', 128000],
    ];

    public function run(VentaService $service): void
    {
        $clientes = Cliente::all()->keyBy(fn (Cliente $c) => self::normalizar($c->nombre));
        $usuario = User::where('email', env('ADMIN_EMAIL', 'admin@admin.co'))->first();

        $creadas = 0;
        $omitidas = [];

        foreach (self::VENTAS as [$fecha, $nombre, $total]) {
            $cliente = $clientes->get(self::normalizar($nombre));

            if (! $cliente) {
                $omitidas[] = "{$nombre} ({$fecha}, \$".number_format($total, 0, ',', '.').')';

                continue;
            }

            $existe = Venta::where('cliente_id', $cliente->id)
                ->whereDate('fecha', $fecha)
                ->where('total', $total)
                ->exists();

            if ($existe) {
                continue;
            }

            $service->crear([
                'cliente_id' => $cliente->id,
                'user_id' => $usuario?->id,
                'fecha' => $fecha,
                'total' => $total,
                'forma_pago' => Venta::FORMA_CONTADO,
                'metodo_pago' => 'efectivo',
            ]);

            $creadas++;
        }

        $this->command?->info("Ventas creadas: {$creadas} de ".count(self::VENTAS).'.');

        if ($omitidas !== []) {
            $this->command?->warn('Omitidas (cliente no existe): '.implode('; ', $omitidas));
        }
    }

    private static function normalizar(string $nombre): string
    {
        return Str::upper(Str::squish($nombre));
    }
}

<?php

namespace Database\Seeders;

use App\Models\Cliente;
use Illuminate\Database\Seeder;

class ClienteSeeder extends Seeder
{
    /**
     * [nombre, local, ciudad]
     *
     * Los locales vacíos se registran como null.
     */
    private const DATOS = [
        ['JESUS DUQUE', 'AUTOSERVICIO EL PAISA', 'SIMAÑA'],
        ['MARIELA BUSTAMANTE', 'DIOS SI BENDICE', 'SIMAÑA'],
        ['SAID RUEDA', 'MINI MARKET LA BENDICION', 'SIMAÑA'],
        ['ARLEY SANCHEZ', 'TIENDA CANDELARIA', 'LA GLORIA'],
        ['HERMIDEZ', 'TIENDA EL CACHA', 'LA GLORIA'],
        ['ESTEFANER BALLONA', 'LA TORCOROMA', 'LA GLORIA'],
        ['ALEXANDER LEON', 'GRALEX', 'LA GLORIA'],
        ['EDITD BONETH', null, 'LA GLORIA'],
        ['AMALIA QUINTERO', null, 'BESOTE'],
        ['SYNDI HERRERA', 'TIENDA LA ESQUINA', 'BESOTE'],
        ['KELLY JHOANA CRUZ', 'LA MILAGROSA', 'LA MATA'],
        ['MERCADITO GUAJIRO', null, 'LA MATA'],
        ['DEIMER ANGARITA', 'LA BODEGUITA', 'LA MATA'],
        ['RAMON PACHECO', 'TIENDA SOL Y LUNA', 'PELAYA'],
        ['AIDYS QUINTERO', 'TIENDA YIRE', 'PELAYA'],
        ['TIENDA DONDE BETO', null, 'PELAYA'],
        ['YURANI CHINCHILLA', 'POLLO JUANES', 'PAILITAS'],
        ['JHON GARCIA', 'Supermercado Jhon', 'PAILITAS'],
        ['LUZ MARINA LEON', 'pollo plas', 'PAILITAS'],
        ['YANETH ARDILA', 'Tienda Luciana', 'PAILITAS'],
        ['RODRIGO OTALVARES', 'tienda concentracion', 'PAILITAS'],
        ['EDY NAVARRO', 'TIENDA ALEXY', 'PAILITAS'],
        ['MILADYS PACHECO', 'miselanea jhovany', 'CURUMANI'],
        ['DIEGO DUQUE', 'abastos y verduras el fenix', 'CURUMANI'],
        ['DANIEL VILLAZON', 'fruber el paraiso', 'CURUMANI'],
        ['ESTELA AVEDAÑO', 'tien el manantial', 'CURUMANI'],
        ['KAREN TAMAYO', 'Distribuciones MyM', 'CURUMANI'],
        ['SRIVEN CABALLERO', 'Salsas', 'CURUMANI'],
        ['LORENA MORALES', 'Tienda la mono de dios', 'SAN ROQUE'],
        ['JHON CAÑIZARES', 'Granero as de oro', 'SAN ROQUE'],
        ['EVERIS LOPEZ', 'CARNITAS ALFA', 'SAN ROQUE'],
        ['ARACELIS MAJICA', 'donde goyo', 'SAN ROQUE'],
        ['OSNEIDER SANGUINO', 'Carnes del Campo', 'SAN ROQUE'],
        ['INGRI BELEÑO', 'friego carnes la principal', 'SAN ROQUE'],
        ['ESNEIDER TELLEZ', 'VERDURAS CATALINA', 'PELAYA'],
    ];

    public function run(): void
    {
        foreach (self::DATOS as [$nombre, $local, $ciudad]) {
            Cliente::updateOrCreate(
                ['nombre' => $nombre, 'ciudad' => $ciudad],
                ['local' => $local],
            );
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\InventarioMateriaPrima;
use App\Models\MateriaPrima;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class MateriaPrimaSeeder extends Seeder
{
    /**
     * [codigo, nombre, es_molido, stock_kg]
     *
     * Los tres últimos (MIEL, mostaza, salsa negra) vienen en galones;
     * como el sistema almacena en gramos (unidad_base kg), se dejan en 0.
     */
    private const DATOS = [
        ['MP-001', 'ACHIOTE PEPA', false, 26.64],
        ['MP-002', 'AJI BOTELLA PURO', false, 0],
        ['MP-003', 'AJO CABEZA', false, 0],
        ['MP-004', 'ALBAHACA', false, 18.73],
        ['MP-005', 'ALUMBRE', false, 4.7],
        ['MP-006', 'ANIS ESTRELLADO', false, 22.8],
        ['MP-007', 'ANIS GRANO', false, 25],
        ['MP-008', 'AJO EN POLVO', false, 4.9],
        ['MP-009', 'AZUCAR DE LECHE', false, 6.74],
        ['MP-010', 'AZUFRE', false, 6.3],
        ['MP-011', 'BICARBONATO', false, 27.6],
        ['MP-012', 'CANELA H2', false, 12.66],
        ['MP-013', 'CANELA MOLIDA', false, 5.56],
        ['MP-014', 'CHIA', false, 3.18],
        ['MP-015', 'CIRUELA PASA', false, 7],
        ['MP-016', 'CLAVO ENTERO', false, 4.58],
        ['MP-017', 'COLOR AMARILLO', false, 32.18],
        ['MP-018', 'COLOR ROJO', false, 27.42],
        ['MP-019', 'COMINO GRANO', false, 25.18],
        ['MP-020', 'CURCUMA', false, 8.38],
        ['MP-021', 'CURRY', false, 34.86],
        ['MP-022', 'FLOR CALENDULA', false, 9.34],
        ['MP-023', 'FLOR JAMAICA', false, 7.28],
        ['MP-024', 'GUASCA', false, 5],
        ['MP-025', 'MIEL', false, 0], // galones
        ['MP-026', 'HIERBABUENA', false, 6.16],
        ['MP-027', 'JENGIBRE', false, 17.54],
        ['MP-028', 'LAUREL HOJA', false, 5.72],
        ['MP-029', 'LAUREL PICADO', false, 11.96],
        ['MP-030', 'MANZANILLA', false, 0.98],
        ['MP-031', 'NUEZ MOSCADA PEPA', false, 16.1],
        ['MP-032', 'OREGANO HOJA', false, 10],
        ['MP-033', 'PAPRIKA', false, 15.02],
        ['MP-034', 'PIMIENTA OLOR', false, 6.2],
        ['MP-035', 'PIMIENTA PICANTE', false, 11.14],
        ['MP-036', 'POLVO DE HORNEAR', false, 1.12],
        ['MP-037', 'GLUTOMATO', false, 7.5],
        ['MP-038', 'ROMERO', false, 11.6],
        ['MP-039', 'SAL DE MORA AZUL', false, 3.58],
        ['MP-040', 'SAL CEREZA', false, 6.82],
        ['MP-041', 'SAL DE LIMON', false, 12.24],
        ['MP-042', 'SAL MARACUYÁ', false, 10],
        ['MP-043', 'TAJIN', false, 6.74],
        ['MP-044', 'TOMILLO HOJA', false, 8.1],
        ['MP-045', 'UVA PASA', false, 20],
        ['MP-046', 'LINAZA', false, 4.84],
        ['MP-047', 'SAZONAR', false, 13.3],
        ['MP-048', 'MAGUIE', false, 7],
        ['MP-049', 'SAL', false, 28],
        ['MP-050', 'AZUCAR', false, 2.5],
        ['MP-051', 'mostaza', false, 0], // galones
        ['MP-052', 'salsa negra', false, 0], // galones
        ['MP-053', 'ADOBO AHUMADO', true, 22.72],
        ['MP-054', 'ADOBO MIXTO', true, 1.2],
        ['MP-055', 'ADOBO CARNE', true, 6.04],
        ['MP-056', 'ADOBO POLLO', true, 5.48],
        ['MP-057', 'AJO FINAS HIERBAS', true, 0],
        ['MP-058', 'CANELA MOLIDA', true, 3.78],
        ['MP-059', 'COMINO MOLIDO', true, 12.26],
        ['MP-060', 'color rojo', true, 27],
        ['MP-061', 'NUEZ MOSC MOLIDA', true, 7.5],
        ['MP-062', 'OREGANO MOLIDO', true, 5.14],
        ['MP-063', 'SAZONADOR M', true, 4.32],
        ['MP-064', 'LAUREL MOLIDO', true, 6.66],
        ['MP-065', 'TOMILLO MOLIDO', true, 13.76],
        ['MP-066', 'LAUREL MOLIDO', true, 3.9],
        ['MP-067', 'SEMILLA DE CILANTRO', true, 2.3],
        ['MP-068', 'AJO EN POLVO', true, 11.88],
        ['MP-069', 'FINAS HIERBAS', true, 10.1],
        ['MP-070', 'AZAFRAN', true, 3.46],
        ['MP-071', 'PIMIENTA MOLIDA', true, 8.96],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::DATOS as [$codigo, $nombre, $esMolido, $stockKg]) {
                $mp = MateriaPrima::updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'nombre' => $nombre,
                        'es_molido' => $esMolido,
                        'unidad_base' => 'kg',
                        'stock_minimo' => 5,
                        'activo' => true,
                    ]
                );

                InventarioMateriaPrima::updateOrCreate(
                    ['materia_prima_id' => $mp->id],
                    ['stock_gramos' => (int) round($stockKg * 1000)]
                );
            }
        });
    }
}
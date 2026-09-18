<?php

namespace Database\Seeders;

use App\Models\DetalleReceta;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RecetaSeeder extends Seeder
{
    /**
     * Receta automática por nombre de producto terminado → materia prima.
     *
     * Cada producto es un empaque de UNA sola materia prima (1:1), por lo que
     * la receta tiene una única línea con `gramos_por_unidad = peso_neto` del
     * producto (aplica a ambas presentaciones ESP y B2C). Idempotente: al
     * re-correrlo reemplaza las líneas de los productos mapeados.
     *
     * Correcciones por diferencias de nomenclatura entre las hojas:
     * - "ADOBO PARA CARNES" ↔ MP "ADOBO CARNE"
     * - "ADOBO PARA POLLO"  ↔ MP "ADOBO POLLO"
     * - "AJO CON FINAS HIERBAS" ↔ MP "AJO FINAS HIERBAS"
     * - "CLAVO OLOR" ↔ MP "CLAVO ENTERO"
     * - "NUEZ MOSCADA MOLIDA" ↔ MP "NUEZ MOSC MOLIDA"
     * - "PIMINTA MOLIDA" (typo) ↔ MP "PIMIENTA MOLIDA"
     * - "UVAS PASA*" ↔ MP "UVA PASA" · "LINAZA ENTERA*" ↔ MP "LINAZA"
     * - "GUASCAS X20" ↔ MP "GUASCA" · "SAZONADOR MIXTO" ↔ MP "SAZONADOR M"
     * - "BICARBONATO X250/X450" ↔ MP "BICARBONATO" · "SAL DE ..." ↔ MP "SAL ..."
     *
     * Colisiones de nombre entre materias primas resueltas por código:
     * - "AJO EN POLVO" → MP-008 (la no-molida, no la gemela es_molido=1)
     * - "COLOR ROJO"  → MP-018 (igual, evita la gemela "color rojo")
     * - "LAUREL MOLIDO" → MP-064 (primera de las dos duplicadas)
     */
    private const MAPEO_NOMBRE = [
        'ACHIOTE PEPA' => 'MP-001',
        'AJI BOTELLA PURO' => 'MP-002',
        'ALBAHACA' => 'MP-004',
        'ALUMBRE' => 'MP-005',
        'ANIS ESTRELLADO' => 'MP-006',
        'ANIS GRANO' => 'MP-007',
        'AJO EN POLVO' => 'MP-008',
        'AZUCAR DE LECHE' => 'MP-009',
        'AZUFRE' => 'MP-010',
        'BICARBONATO' => 'MP-011',
        'BICARBONATO X250' => 'MP-011',
        'BICARBONATO X450' => 'MP-011',
        'CHIA' => 'MP-014',
        'CHIA 250G' => 'MP-014',
        'CIRUELA PASA' => 'MP-015',
        'CLAVO OLOR' => 'MP-016',
        'COLOR AMARILLO' => 'MP-017',
        'COLOR ROJO' => 'MP-018',
        'COMINO GRANO' => 'MP-019',
        'CURCUMA' => 'MP-020',
        'CURRY' => 'MP-021',
        'FLOR CALENDULA' => 'MP-022',
        'FLOR JAMAICA' => 'MP-023',
        'GUASCAS X20' => 'MP-024',
        'MIEL' => 'MP-025',
        'HIERBABUENA' => 'MP-026',
        'JENGIBRE' => 'MP-027',
        'LAUREL HOJA' => 'MP-028',
        'MANZANILLA' => 'MP-030',
        'NUEZ MOSCADA PEPA' => 'MP-031',
        'OREGANO HOJA' => 'MP-032',
        'PAPRIKA' => 'MP-033',
        'PIMIENTA OLOR' => 'MP-034',
        'PIMIENTA PICANTE' => 'MP-035',
        'POLVO DE HORNEAR' => 'MP-036',
        'ROMERO' => 'MP-038',
        'SAL DE MORA AZUL' => 'MP-039',
        'SAL DE CEREZA' => 'MP-040',
        'SAL DE LIMON' => 'MP-041',
        'SAL DE MARACUYA' => 'MP-042',
        'TAJIN' => 'MP-043',
        'TOMILLO HOJA' => 'MP-044',
        'UVAS PASA' => 'MP-045',
        'UVAS PASA 55G' => 'MP-045',
        'UVAS PASA 175G' => 'MP-045',
        'LINAZA ENTERA' => 'MP-046',
        'LINAZA ENTERA 250G' => 'MP-046',
        'MOSTAZA' => 'MP-051',
        'SALSA NEGRA' => 'MP-052',
        'ADOBO AHUMADO' => 'MP-053',
        'ADOBO MIXTO' => 'MP-054',
        'ADOBO PARA CARNES' => 'MP-055',
        'ADOBO PARA POLLO' => 'MP-056',
        'AJO CON FINAS HIERBAS' => 'MP-057',
        'COMINO MOLIDO' => 'MP-059',
        'NUEZ MOSCADA MOLIDA' => 'MP-061',
        'OREGANO MOLIDO' => 'MP-062',
        'SAZONADOR MIXTO' => 'MP-063',
        'LAUREL MOLIDO' => 'MP-064',
        'TOMILLO MOLIDO' => 'MP-065',
        'FINAS HIERBAS' => 'MP-069',
        'AZAFRAN' => 'MP-070',
        'PIMINTA MOLIDA' => 'MP-071',
    ];

    public function run(): void
    {
        $mpPorCodigo = MateriaPrima::pluck('id', 'codigo');
        $productos = ProductoTerminado::get();

        $creadas = 0;
        $omitidas = [];

        DB::transaction(function () use ($productos, $mpPorCodigo, &$creadas, &$omitidas) {
            foreach ($productos as $pt) {
                $codigoMp = self::MAPEO_NOMBRE[$pt->nombre] ?? null;

                if ($codigoMp === null || ! isset($mpPorCodigo[$codigoMp])) {
                    $omitidas[] = $pt->nombre;

                    continue;
                }

                $pt->receta()->delete();
                DetalleReceta::create([
                    'producto_terminado_id' => $pt->id,
                    'materia_prima_id' => $mpPorCodigo[$codigoMp],
                    'gramos_por_unidad' => (float) $pt->peso_neto,
                ]);

                $creadas++;
            }
        });

        $this->command->info("Recetas creadas/actualizadas: {$creadas}");
        if ($omitidas) {
            $this->command->warn('Sin materia prima equivalente (omitidas): '.implode(', ', array_unique($omitidas)));
        }
    }
}
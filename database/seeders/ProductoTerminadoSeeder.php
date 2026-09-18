<?php

namespace Database\Seeders;

use App\Models\InventarioProductoTerminado;
use App\Models\ProductoTerminado;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductoTerminadoSeeder extends Seeder
{
    /**
     * Cada "Cant. ESP/B2C" de la hoja representa un PAQUETE de esta cantidad
     * de unidades; el stock disponible se almacena en unidades individuales.
     */
    private const UNIDADES_POR_PAQUETE = 20;

    /**
     * [codigo, nombre, categoria (ESP|B2C), peso_neto (g), precio_venta, paquetes]
     *
     * Precios unitarios ("VR Disp") interpretados en COP, best-effort; los
     * "VR TOTAL" de la hoja tienen errores y se ignoran. Presentaciones sin
     * gramaje se omiten. `Almendra`, `Miel`, `Mostaza`, etc. con datos solo
     * en una columna generan un único registro.
     */
    private const DATOS = [
        ['PT-002A', 'ACHIOTE PEPA', 'ESP', 15, 24000, 14],
        ['PT-002B', 'ACHIOTE PEPA', 'B2C', 8, 13000, 8],
        ['PT-003A', 'ADOBO AHUMADO', 'ESP', 12, 24000, 22],
        ['PT-003B', 'ADOBO AHUMADO', 'B2C', 6, 13000, 19],
        ['PT-004A', 'ADOBO MIXTO', 'ESP', 20, 24000, 18],
        ['PT-004B', 'ADOBO MIXTO', 'B2C', 13, 13000, 6],
        ['PT-005A', 'ADOBO PARA CARNES', 'ESP', 20, 24000, 13],
        ['PT-005B', 'ADOBO PARA CARNES', 'B2C', 13, 13000, 6],
        ['PT-006A', 'ADOBO PARA POLLO', 'ESP', 20, 24000, 20],
        ['PT-006B', 'ADOBO PARA POLLO', 'B2C', 13, 13000, 16],
        ['PT-007A', 'AJI BOTELLA PURO', 'ESP', 225, 24000, 0],
        ['PT-008A', 'AJO EN POLVO', 'ESP', 27, 24000, 9],
        ['PT-008B', 'AJO EN POLVO', 'B2C', 18, 0, 20],
        ['PT-009A', 'AJO CON FINAS HIERBAS', 'ESP', 25, 24000, 1],
        ['PT-009B', 'AJO CON FINAS HIERBAS', 'B2C', 8, 13000, 13],
        ['PT-010A', 'ALUMBRE', 'ESP', 30, 24000, 11],
        ['PT-010B', 'ALUMBRE', 'B2C', 20, 13000, 3],
        ['PT-011A', 'ALBAHACA', 'ESP', 5, 24000, 21],
        ['PT-011B', 'ALBAHACA', 'B2C', 4, 13000, 21],
        ['PT-012A', 'ALMENDRA', 'ESP', 25, 24000, 0],
        ['PT-013A', 'ANIS ESTRELLADO', 'ESP', 4, 24000, 5],
        ['PT-013B', 'ANIS ESTRELLADO', 'B2C', 3, 13000, 18],
        ['PT-014A', 'ANIS GRANO', 'ESP', 8, 24000, 21],
        ['PT-014B', 'ANIS GRANO', 'B2C', 4, 13000, 10],
        ['PT-015A', 'AZAFRAN', 'ESP', 22, 24000, 7],
        ['PT-015B', 'AZAFRAN', 'B2C', 15, 13000, 7],
        ['PT-016A', 'AZUCAR DE LECHE', 'ESP', 15, 24000, 12],
        ['PT-016B', 'AZUCAR DE LECHE', 'B2C', 8, 13000, 0],
        ['PT-017A', 'AZUFRE', 'ESP', 25, 24000, 3],
        ['PT-017B', 'AZUFRE', 'B2C', 15, 13000, 10],
        ['PT-018A', 'AZUL ROPA', 'ESP', 12, 24000, 0],
        ['PT-018B', 'AZUL ROPA', 'B2C', 6, 13000, 0],
        ['PT-019A', 'BICARBONATO', 'ESP', 35, 24000, 22],
        ['PT-019B', 'BICARBONATO', 'B2C', 18, 13000, 10],
        ['PT-020A', 'BICARBONATO X250', 'ESP', 250, 2.90, 42],
        ['PT-021A', 'BICARBONATO X450', 'ESP', 450, 4.60, 28],
        ['PT-027A', 'CHIA', 'ESP', 10, 24000, 16],
        ['PT-027B', 'CHIA', 'B2C', 6, 0, 12],
        ['PT-028A', 'CIRUELA PASA', 'ESP', 50, 24000, 8],
        ['PT-029A', 'CLAVO OLOR', 'ESP', 6, 24000, 0],
        ['PT-029B', 'CLAVO OLOR', 'B2C', 3, 13000, 7],
        ['PT-030A', 'CLORO PASTILLA', 'ESP', 1, 24000, 0],
        ['PT-031A', 'COLOR ROJO', 'ESP', 30, 24000, 5],
        ['PT-031B', 'COLOR ROJO', 'B2C', 15, 13000, 10],
        ['PT-032A', 'COLOR AMARILLO', 'ESP', 30, 24000, 28],
        ['PT-032B', 'COLOR AMARILLO', 'B2C', 15, 13000, 15],
        ['PT-033A', 'COMINO GRANO', 'ESP', 10, 24000, 17],
        ['PT-033B', 'COMINO GRANO', 'B2C', 6, 13000, 8],
        ['PT-034A', 'COMINO MOLIDO', 'ESP', 20, 24000, 15],
        ['PT-034B', 'COMINO MOLIDO', 'B2C', 12, 13000, 15],
        ['PT-035A', 'CURRY', 'ESP', 18, 24000, 24],
        ['PT-035B', 'CURRY', 'B2C', 12, 13000, 3],
        ['PT-036A', 'CURCUMA', 'ESP', 9, 24000, 3],
        ['PT-036B', 'CURCUMA', 'B2C', 6, 13000, 6],
        ['PT-037A', 'FLOR CALENDULA', 'ESP', 5, 24000, 14],
        ['PT-037B', 'FLOR CALENDULA', 'B2C', 3, 13000, 10],
        ['PT-038A', 'FLOR JAMAICA', 'ESP', 8, 24000, 7],
        ['PT-038B', 'FLOR JAMAICA', 'B2C', 5, 13000, 0],
        ['PT-039A', 'FINAS HIERBAS', 'ESP', 8, 24000, 18],
        ['PT-039B', 'FINAS HIERBAS', 'B2C', 5, 13000, 2],
        ['PT-040A', 'GUASCAS X20', 'ESP', 5, 24000, 12],
        ['PT-040B', 'GUASCAS X20', 'B2C', 3, 13000, 35],
        ['PT-041A', 'HIERBABUENA', 'ESP', 6, 24000, 25],
        ['PT-041B', 'HIERBABUENA', 'B2C', 3, 13000, 17],
        ['PT-042A', 'JENGIBRE', 'ESP', 10, 24000, 6],
        ['PT-042B', 'JENGIBRE', 'B2C', 4, 13000, 3],
        ['PT-043A', 'LAUREL HOJA', 'ESP', 6, 24000, 0],
        ['PT-043B', 'LAUREL HOJA', 'B2C', 3, 13000, 0],
        ['PT-044A', 'LAUREL MOLIDO', 'ESP', 20, 24000, 13],
        ['PT-044B', 'LAUREL MOLIDO', 'B2C', 10, 13000, 1],
        ['PT-045A', 'LINAZA ENTERA', 'ESP', 20, 24000, 3],
        ['PT-045B', 'LINAZA ENTERA', 'B2C', 12, 13000, 18],
        ['PT-046A', 'LINAZA ENTERA 250G', 'ESP', 250, 24000, 0],
        ['PT-047A', 'MANZANILLA', 'ESP', 6, 24000, 3],
        ['PT-047B', 'MANZANILLA', 'B2C', 3, 13000, 9],
        ['PT-048A', 'MIEL', 'ESP', 35, 24000, 3],
        ['PT-048B', 'MIEL', 'B2C', 20, 15000, 0],
        ['PT-049A', 'MOSTAZA', 'ESP', 35, 24000, 3],
        ['PT-049B', 'MOSTAZA', 'B2C', 25, 13000, 0],
        ['PT-050A', 'NUEZ MOSCADA PEPA', 'ESP', 1, 24000, 0],
        ['PT-051A', 'NUEZ MOSCADA MOLIDA', 'ESP', 10, 24000, 19],
        ['PT-051B', 'NUEZ MOSCADA MOLIDA', 'B2C', 5, 13000, 15],
        ['PT-052A', 'OREGANO HOJA', 'ESP', 6, 24000, 1],
        ['PT-052B', 'OREGANO HOJA', 'B2C', 3, 13000, 0],
        ['PT-053A', 'OREGANO MOLIDO', 'ESP', 20, 24000, 0],
        ['PT-053B', 'OREGANO MOLIDO', 'B2C', 10, 13000, 10],
        ['PT-054A', 'PAPRIKA', 'ESP', 14, 24000, 0],
        ['PT-054B', 'PAPRIKA', 'B2C', 6, 13000, 3],
        ['PT-055A', 'PIMIENTA OLOR', 'ESP', 8, 24000, 8],
        ['PT-055B', 'PIMIENTA OLOR', 'B2C', 4, 13000, 16],
        ['PT-056A', 'PIMINTA MOLIDA', 'ESP', 18, 24000, 13],
        ['PT-056B', 'PIMINTA MOLIDA', 'B2C', 9, 13000, 9],
        ['PT-057A', 'PIMIENTA PICANTE', 'ESP', 8, 24000, 21],
        ['PT-057B', 'PIMIENTA PICANTE', 'B2C', 4, 13000, 11],
        ['PT-058A', 'POLVO DE HORNEAR', 'ESP', 15, 24000, 5],
        ['PT-058B', 'POLVO DE HORNEAR', 'B2C', 7, 13000, 0],
        ['PT-059A', 'ROMERO', 'ESP', 6, 24000, 17],
        ['PT-059B', 'ROMERO', 'B2C', 4, 13000, 14],
        ['PT-060A', 'SALSA NEGRA', 'ESP', 37, 24000, 13],
        ['PT-060B', 'SALSA NEGRA', 'B2C', 25, 13000, 0],
        ['PT-061A', 'SAZONADOR MIXTO', 'ESP', 20, 24000, 21],
        ['PT-061B', 'SAZONADOR MIXTO', 'B2C', 15, 13000, 7],
        ['PT-062A', 'TAJIN', 'ESP', 10, 24000, 12],
        ['PT-062B', 'TAJIN', 'B2C', 5, 13000, 15],
        ['PT-063A', 'TOMILLO HOJA', 'ESP', 6, 24000, 11],
        ['PT-063B', 'TOMILLO HOJA', 'B2C', 3, 13000, 14],
        ['PT-064A', 'TOMILLO MOLIDO', 'ESP', 20, 24000, 5],
        ['PT-064B', 'TOMILLO MOLIDO', 'B2C', 10, 13000, 2],
        ['PT-065A', 'UVAS PASA', 'ESP', 18, 24000, 0],
        ['PT-065B', 'UVAS PASA', 'B2C', 13, 0, 4],
        ['PT-066A', 'UVAS PASA 55G', 'ESP', 55, 24000, 11],
        ['PT-067A', 'UVAS PASA 175G', 'ESP', 175, 24000, 37],
        ['PT-068A', 'SAL DE MARACUYA', 'ESP', 15, 24000, 7],
        ['PT-068B', 'SAL DE MARACUYA', 'B2C', 7, 13000, 0],
        ['PT-069A', 'SAL DE MORA AZUL', 'ESP', 15, 24000, 8],
        ['PT-069B', 'SAL DE MORA AZUL', 'B2C', 7, 13000, 0],
        ['PT-070A', 'SAL DE LIMON', 'ESP', 15, 24000, 10],
        ['PT-070B', 'SAL DE LIMON', 'B2C', 7, 13000, 0],
        ['PT-071A', 'SAL DE CEREZA', 'ESP', 15, 24000, 1],
        ['PT-071B', 'SAL DE CEREZA', 'B2C', 7, 13000, 0],
        ['PT-072A', 'CHIA 250G', 'ESP', 250, 24000, 16],
    ];

    public function run(): void
    {
        DB::transaction(function () {
            foreach (self::DATOS as [$codigo, $nombre, $categoria, $pesoNeto, $precio, $paquetes]) {
                $pt = ProductoTerminado::updateOrCreate(
                    ['codigo' => $codigo],
                    [
                        'nombre' => $nombre,
                        'categoria' => $categoria,
                        'presentacion' => 'bolsa',
                        'peso_neto' => $pesoNeto,
                        'precio_venta' => $precio,
                        'stock_minimo' => 0,
                        'activo' => true,
                    ]
                );

                InventarioProductoTerminado::updateOrCreate(
                    ['producto_terminado_id' => $pt->id],
                    ['disponible' => $paquetes * self::UNIDADES_POR_PAQUETE]
                );
            }
        });
    }
}
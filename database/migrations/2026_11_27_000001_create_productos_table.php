<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Agrupa las presentaciones (filas de producto_terminados) bajo un producto padre.
 * Cada presentación conserva su stock, receta, kardex y movimientos del camión;
 * solo se agrega producto_id para saber a qué producto pertenece.
 */
return new class extends Migration
{
    /** Tamaños que hoy son productos aparte y pasan a ser presentaciones del producto base. */
    private const UNIR = [
        'BICARBONATO X250' => 'BICARBONATO',
        'BICARBONATO X450' => 'BICARBONATO',
        'CHIA 250G' => 'CHIA',
        'LINAZA ENTERA 250G' => 'LINAZA ENTERA',
        'UVAS PASA 55G' => 'UVAS PASA',
        'UVAS PASA 175G' => 'UVAS PASA',
    ];

    public function up(): void
    {
        Schema::create('productos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::table('producto_terminados', function (Blueprint $table) {
            $table->foreignId('producto_id')->nullable()->after('id')
                ->constrained('productos')->restrictOnDelete();
        });

        $this->poblar();
    }

    private function poblar(): void
    {
        $presentaciones = DB::table('producto_terminados')->select('id', 'nombre', 'activo')->orderBy('id')->get();
        $ahora = now();
        $productos = []; // nombre base => id

        foreach ($presentaciones as $p) {
            $nombre = trim((string) $p->nombre);
            $base = self::UNIR[mb_strtoupper($nombre)] ?? $nombre;

            if (! isset($productos[$base])) {
                $productos[$base] = DB::table('productos')->insertGetId([
                    'nombre' => $base,
                    'activo' => false,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }

            DB::table('producto_terminados')->where('id', $p->id)->update([
                'producto_id' => $productos[$base],
                'nombre' => $base,
            ]);

            // El producto queda activo si alguna de sus presentaciones lo está
            if ($p->activo) {
                DB::table('productos')->where('id', $productos[$base])->update(['activo' => true]);
            }
        }
    }

    public function down(): void
    {
        // Devuelve el nombre de tamaño a las presentaciones unidas (se distinguen por peso)
        foreach (self::UNIR as $original => $base) {
            if (preg_match('/(\d+)G?$/i', $original, $m)) {
                DB::table('producto_terminados')
                    ->where('nombre', $base)
                    ->where('peso_neto', (float) $m[1])
                    ->update(['nombre' => $original]);
            }
        }

        Schema::table('producto_terminados', function (Blueprint $table) {
            $table->dropConstrainedForeignId('producto_id');
        });

        Schema::dropIfExists('productos');
    }
};

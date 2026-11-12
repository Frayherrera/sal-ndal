<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias_gasto', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            $table->string('descripcion', 255)->nullable();
            // Identificador fijo para categorías que usa el sistema (ej. compras automáticas)
            $table->string('clave', 50)->nullable()->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        // Categorías iniciales (se insertan aquí para que existan también en producción)
        $ahora = now();
        $categorias = [
            ['nombre' => 'Compra de materia prima', 'clave' => 'compra_materia_prima', 'descripcion' => 'Generado automáticamente al registrar compras con costo en inventario.'],
            ['nombre' => 'Empaques y etiquetas', 'clave' => null, 'descripcion' => 'Bolsas, frascos, tapas, etiquetas.'],
            ['nombre' => 'Arriendo', 'clave' => null, 'descripcion' => null],
            ['nombre' => 'Servicios públicos', 'clave' => null, 'descripcion' => 'Luz, agua, gas, internet, teléfono.'],
            ['nombre' => 'Transporte y fletes', 'clave' => null, 'descripcion' => 'Envíos, domicilios, gasolina.'],
            ['nombre' => 'Nómina', 'clave' => null, 'descripcion' => 'Sueldos, prestaciones y pagos a personal.'],
            ['nombre' => 'Mantenimiento', 'clave' => null, 'descripcion' => 'Reparaciones de equipos y local.'],
            ['nombre' => 'Impuestos y trámites', 'clave' => null, 'descripcion' => null],
            ['nombre' => 'Publicidad', 'clave' => null, 'descripcion' => null],
            ['nombre' => 'Otros', 'clave' => null, 'descripcion' => null],
        ];

        DB::table('categorias_gasto')->insert(array_map(fn ($c) => $c + [
            'activo' => true,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ], $categorias));
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias_gasto');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('detalle_venta', function (Blueprint $table) {
            $table->dropColumn('unidades_por_paquete');
        });

        Schema::table('detalle_venta', function (Blueprint $table) {
            $table->renameColumn('cantidad_paquetes', 'cantidad_unidades');
            $table->renameColumn('precio_paquete', 'precio_unidad');
        });
    }

    public function down(): void
    {
        Schema::table('detalle_venta', function (Blueprint $table) {
            $table->renameColumn('cantidad_unidades', 'cantidad_paquetes');
            $table->renameColumn('precio_unidad', 'precio_paquete');
        });

        Schema::table('detalle_venta', function (Blueprint $table) {
            $table->unsignedInteger('unidades_por_paquete')->default(20);
        });
    }
};

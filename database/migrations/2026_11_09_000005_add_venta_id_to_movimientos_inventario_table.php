<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('venta_id')
                ->nullable()
                ->after('conteo_fisico_id')
                ->constrained('ventas')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('venta_id');
        });
    }
};

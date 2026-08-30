<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->string('tipo'); // compra_recepcion, consumo_produccion, producto_producido, venta_despacho, devolucion, ajuste_positivo, ajuste_negativo, anulacion_reversion
            $table->morphs('origen'); // materia_prima | producto_terminado
            $table->bigInteger('cantidad'); // en gramos para MP, unidades para PT
            $table->string('direccion'); // entrada | salida
            $table->bigInteger('saldo')->default(0);
            $table->string('documento')->nullable();
            $table->string('referencia')->nullable();
            $table->text('motivo')->nullable();
            $table->decimal('costo_unitario', 14, 4)->nullable();
            $table->decimal('costo_total', 14, 2)->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('movimiento_original_id')->nullable()->constrained('movimientos_inventario')->nullOnDelete();
            $table->foreignId('conteo_fisico_id')->nullable()->constrained('conteos_fisicos')->nullOnDelete();
            $table->timestamp('fecha');
            $table->timestamps();

            // morphs('origen') ya crea el índice origen_type + origen_id
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};

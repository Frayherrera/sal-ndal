<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_conteo_fisico', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conteo_fisico_id')->constrained('conteos_fisicos')->cascadeOnDelete();
            $table->foreignId('materia_prima_id')->nullable()->constrained('materias_primas')->nullOnDelete();
            $table->foreignId('producto_terminado_id')->nullable()->constrained('producto_terminados')->nullOnDelete();
            $table->bigInteger('stock_sistema')->default(0);
            $table->bigInteger('cantidad_fisica')->default(0);
            $table->bigInteger('diferencia')->default(0);
            $table->text('motivo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_conteo_fisico');
    }
};

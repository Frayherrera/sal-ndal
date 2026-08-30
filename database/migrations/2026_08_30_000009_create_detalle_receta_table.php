<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_receta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_terminado_id')->constrained('producto_terminados')->cascadeOnDelete();
            $table->foreignId('materia_prima_id')->constrained('materias_primas')->cascadeOnDelete();
            $table->decimal('gramos_por_unidad', 12, 3)->default(0); // gramos de MP por 1 unidad de PT
            $table->timestamps();

            $table->unique(['producto_terminado_id', 'materia_prima_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_receta');
    }
};

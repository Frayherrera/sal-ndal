<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_materia_prima', function (Blueprint $table) {
            $table->id();
            $table->foreignId('materia_prima_id')->constrained('materias_primas')->cascadeOnDelete();
            $table->bigInteger('stock_gramos')->default(0);
            $table->decimal('costo_promedio', 14, 2)->default(0); // costo por kg
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_materia_prima');
    }
};

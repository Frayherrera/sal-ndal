<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_molido', function (Blueprint $table) {
            $table->id();
            $table->foreignId('molido_id')->constrained('materias_primas')->cascadeOnDelete();
            $table->foreignId('ingrediente_id')->constrained('materias_primas')->cascadeOnDelete();
            $table->decimal('gramos_por_kg', 12, 3)->default(0); // gramos del ingrediente por 1 kg de molido
            $table->timestamps();

            $table->unique(['molido_id', 'ingrediente_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_molido');
    }
};

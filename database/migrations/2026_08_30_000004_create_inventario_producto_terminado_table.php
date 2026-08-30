<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventario_producto_terminado', function (Blueprint $table) {
            $table->id();
            $table->foreignId('producto_terminado_id')->constrained('producto_terminados')->cascadeOnDelete();
            $table->bigInteger('disponible')->default(0);
            $table->bigInteger('comprometido')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventario_producto_terminado');
    }
};

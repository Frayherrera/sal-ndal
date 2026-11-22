<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rutas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 80)->unique();
            // Posición en la rotación semanal (1, 2, 3…): el siguiente viaje sugiere la ruta que sigue
            $table->unsignedSmallInteger('orden')->default(1);
            // Ciudades que visita (coinciden con clientes.ciudad); sirven para cruzar las ventas de la ruta
            $table->json('ciudades')->nullable();
            $table->string('descripcion', 255)->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rutas');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('conteos_fisicos', function (Blueprint $table) {
            $table->id();
            $table->string('codigo')->unique();
            $table->string('tipo'); // materia_prima | producto_terminado
            $table->string('estado')->default('borrador'); // borrador | completado | aprobado | anulado
            $table->date('fecha_conteo');
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('fecha_completado')->nullable();
            $table->timestamp('fecha_aprobado')->nullable();
            $table->timestamp('fecha_anulado')->nullable();
            $table->foreignId('aprobado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conteos_fisicos');
    }
};

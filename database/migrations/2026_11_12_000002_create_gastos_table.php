<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gastos', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();
            $table->date('fecha');
            $table->foreignId('categoria_gasto_id')->constrained('categorias_gasto')->restrictOnDelete();
            $table->string('descripcion', 255);
            $table->decimal('monto', 14, 2);
            $table->string('metodo_pago', 20)->nullable();
            $table->string('proveedor', 120)->nullable();
            $table->string('estado', 20)->default('registrado');
            // Gasto generado automáticamente desde una compra de inventario
            $table->foreignId('movimiento_inventario_id')->nullable()->unique()
                ->constrained('movimientos_inventario')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_at')->nullable();
            $table->foreignId('anulado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->timestamps();

            $table->index(['fecha', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gastos');
    }
};

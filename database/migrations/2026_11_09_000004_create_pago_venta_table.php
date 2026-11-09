<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pago_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->timestamp('fecha');
            $table->decimal('monto', 14, 2);
            $table->string('metodo', 30)->default('efectivo'); // efectivo | transferencia | nequi | daviplata | otro
            $table->string('referencia')->nullable();
            $table->text('nota')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();

            $table->index(['venta_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pago_venta');
    }
};

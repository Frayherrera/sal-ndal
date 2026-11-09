<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('detalle_venta', function (Blueprint $table) {
            $table->id();
            $table->foreignId('venta_id')->constrained('ventas')->cascadeOnDelete();
            $table->foreignId('producto_terminado_id')->constrained('producto_terminados')->restrictOnDelete();
            $table->unsignedInteger('cantidad_paquetes');
            $table->unsignedInteger('unidades_por_paquete')->default(20);
            $table->decimal('precio_paquete', 14, 2);
            $table->string('descuento_tipo', 20)->default('monto'); // porcentaje | monto
            $table->decimal('descuento_valor', 14, 2)->default(0);
            $table->decimal('descuento_monto', 14, 2)->default(0);
            $table->decimal('subtotal', 14, 2)->default(0); // bruto (paquetes × precio)
            $table->decimal('total', 14, 2)->default(0); // neto (bruto − descuento)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('detalle_venta');
    }
};

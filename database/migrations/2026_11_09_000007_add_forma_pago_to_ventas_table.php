<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda la forma de pago elegida en el formulario (switch Contado / Crédito).
     *
     * Antes se derivaba del saldo, lo que etiquetaba como "Contado" una venta a
     * crédito que ya había sido pagada por completo.
     */
    public function up(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->string('forma_pago', 10)->default('credito')->after('estado');
        });
    }

    public function down(): void
    {
        Schema::table('ventas', function (Blueprint $table) {
            $table->dropColumn('forma_pago');
        });
    }
};

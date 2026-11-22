<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('viajes_camion', function (Blueprint $table) {
            $table->id();
            $table->string('numero', 20)->nullable()->unique();
            $table->foreignId('ruta_id')->constrained('rutas')->restrictOnDelete();
            $table->date('fecha_salida');
            $table->date('fecha_regreso')->nullable();
            $table->string('estado', 20)->default('en_ruta'); // en_ruta | cerrado | anulado
            $table->text('observaciones')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('cerrado_at')->nullable();
            $table->foreignId('cerrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('anulado_at')->nullable();
            $table->string('motivo_anulacion', 500)->nullable();
            $table->timestamps();

            $table->index(['estado', 'fecha_salida']);
        });

        // Todas las cantidades en UNIDADES (los formularios capturan paquetes de 20 + sueltas)
        Schema::create('detalle_viaje_camion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('viaje_camion_id')->constrained('viajes_camion')->cascadeOnDelete();
            $table->foreignId('producto_terminado_id')->constrained('producto_terminados')->restrictOnDelete();
            $table->unsignedInteger('inicial')->default(0);   // venía en el camión del viaje anterior
            $table->unsignedInteger('surtido')->default(0);   // se cargó desde bodega
            $table->unsignedInteger('bajado')->default(0);    // se bajó del camión a bodega
            $table->unsignedInteger('cargado')->default(0);   // salió: inicial + surtido − bajado
            $table->unsignedInteger('regresado')->nullable(); // volvió en buen estado (al cerrar)
            $table->unsignedInteger('danado')->nullable();
            $table->unsignedInteger('regalado')->nullable();
            $table->unsignedInteger('vendido')->nullable();   // cargado − regresado − dañado − regalado
            $table->decimal('precio_unidad', 14, 2)->default(0); // precio unitario al cargar (valoriza)
            $table->timestamps();

            // Nombre explícito: el automático pasa de los 64 caracteres que admite MySQL
            $table->unique(['viaje_camion_id', 'producto_terminado_id'], 'detalle_viaje_producto_unique');
        });

        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->foreignId('viaje_camion_id')->nullable()->after('venta_id')
                ->constrained('viajes_camion')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropConstrainedForeignId('viaje_camion_id');
        });

        Schema::dropIfExists('detalle_viaje_camion');
        Schema::dropIfExists('viajes_camion');
    }
};

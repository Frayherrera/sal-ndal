<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleVenta extends Model
{
    use HasFactory;

    protected $table = 'detalle_venta';

    protected $fillable = [
        'venta_id',
        'producto_terminado_id',
        'cantidad_unidades',
        'precio_unidad',
        'descuento_tipo',
        'descuento_valor',
        'descuento_monto',
        'subtotal',
        'total',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_unidades' => 'integer',
            'precio_unidad' => 'decimal:2',
            'descuento_valor' => 'decimal:2',
            'descuento_monto' => 'decimal:2',
            'subtotal' => 'decimal:2',
            'total' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class, 'producto_terminado_id');
    }

    /**
     * Unidades que descuenta esta línea del inventario.
     */
    public function unidades(): int
    {
        return $this->cantidad_unidades;
    }
}

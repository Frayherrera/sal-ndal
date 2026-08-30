<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MovimientoInventario extends Model
{
    use HasFactory;

    protected $table = 'movimientos_inventario';

    public const TIPOS = [
        'compra_recepcion' => 'Compra / Recepción',
        'consumo_produccion' => 'Consumo de producción',
        'producto_producido' => 'Producto producido',
        'venta_despacho' => 'Venta / Despacho',
        'devolucion' => 'Devolución',
        'ajuste_positivo' => 'Ajuste positivo',
        'ajuste_negativo' => 'Ajuste negativo',
        'anulacion_reversion' => 'Anulación / Reversión',
    ];

    protected $fillable = [
        'tipo',
        'origen_type',
        'origen_id',
        'cantidad',
        'direccion',
        'saldo',
        'documento',
        'referencia',
        'motivo',
        'costo_unitario',
        'costo_total',
        'user_id',
        'movimiento_original_id',
        'conteo_fisico_id',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'integer',
            'saldo' => 'integer',
            'costo_unitario' => 'decimal:4',
            'costo_total' => 'decimal:2',
            'fecha' => 'datetime',
        ];
    }

    public function origen(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function movimientoOriginal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'movimiento_original_id');
    }

    public function conteoFisico(): BelongsTo
    {
        return $this->belongsTo(ConteoFisico::class);
    }
}

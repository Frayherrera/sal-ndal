<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Línea de un viaje: un producto con sus cantidades en UNIDADES.
 */
class DetalleViajeCamion extends Model
{
    use HasFactory;

    protected $table = 'detalle_viaje_camion';

    protected $fillable = [
        'viaje_camion_id',
        'producto_terminado_id',
        'inicial',
        'surtido',
        'bajado',
        'cargado',
        'regresado',
        'danado',
        'regalado',
        'vendido',
        'precio_unidad',
    ];

    protected function casts(): array
    {
        return [
            'inicial' => 'integer',
            'surtido' => 'integer',
            'bajado' => 'integer',
            'cargado' => 'integer',
            'regresado' => 'integer',
            'danado' => 'integer',
            'regalado' => 'integer',
            'vendido' => 'integer',
            'precio_unidad' => 'decimal:2',
        ];
    }

    public function viaje(): BelongsTo
    {
        return $this->belongsTo(ViajeCamion::class, 'viaje_camion_id');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class, 'producto_terminado_id');
    }

    public function valorVendido(): float
    {
        return round(($this->vendido ?? 0) * (float) $this->precio_unidad, 2);
    }

    public function valorMerma(): float
    {
        return round((($this->danado ?? 0) + ($this->regalado ?? 0)) * (float) $this->precio_unidad, 2);
    }
}

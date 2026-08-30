<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleConteoFisico extends Model
{
    use HasFactory;

    protected $table = 'detalle_conteo_fisico';

    protected $fillable = [
        'conteo_fisico_id',
        'materia_prima_id',
        'producto_terminado_id',
        'stock_sistema',
        'cantidad_fisica',
        'diferencia',
        'motivo',
    ];

    protected function casts(): array
    {
        return [
            'stock_sistema' => 'integer',
            'cantidad_fisica' => 'integer',
            'diferencia' => 'integer',
        ];
    }

    public function conteoFisico(): BelongsTo
    {
        return $this->belongsTo(ConteoFisico::class);
    }

    public function materiaPrima(): BelongsTo
    {
        return $this->belongsTo(MateriaPrima::class);
    }

    public function productoTerminado(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class);
    }
}

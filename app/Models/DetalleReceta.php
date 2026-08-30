<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleReceta extends Model
{
    use HasFactory;

    protected $table = 'detalle_receta';

    protected $fillable = [
        'producto_terminado_id',
        'materia_prima_id',
        'gramos_por_unidad',
    ];

    protected function casts(): array
    {
        return [
            'gramos_por_unidad' => 'decimal:3',
        ];
    }

    public function productoTerminado(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class, 'producto_terminado_id');
    }

    public function materiaPrima(): BelongsTo
    {
        return $this->belongsTo(MateriaPrima::class, 'materia_prima_id');
    }
}

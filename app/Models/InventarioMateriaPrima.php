<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioMateriaPrima extends Model
{
    use HasFactory;

    protected $table = 'inventario_materia_prima';

    protected $fillable = [
        'materia_prima_id',
        'stock_gramos',
        'costo_promedio',
    ];

    protected function casts(): array
    {
        return [
            'stock_gramos' => 'integer',
            'costo_promedio' => 'decimal:2',
        ];
    }

    public function materiaPrima(): BelongsTo
    {
        return $this->belongsTo(MateriaPrima::class);
    }
}

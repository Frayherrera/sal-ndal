<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleMolido extends Model
{
    use HasFactory;

    protected $table = 'detalle_molido';

    protected $fillable = [
        'molido_id',
        'ingrediente_id',
        'gramos_por_kg',
    ];

    protected function casts(): array
    {
        return [
            'gramos_por_kg' => 'decimal:3',
        ];
    }

    public function molido(): BelongsTo
    {
        return $this->belongsTo(MateriaPrima::class, 'molido_id');
    }

    public function ingrediente(): BelongsTo
    {
        return $this->belongsTo(MateriaPrima::class, 'ingrediente_id');
    }
}

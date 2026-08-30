<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarioProductoTerminado extends Model
{
    use HasFactory;

    protected $table = 'inventario_producto_terminado';

    protected $fillable = [
        'producto_terminado_id',
        'disponible',
        'comprometido',
    ];

    protected function casts(): array
    {
        return [
            'disponible' => 'integer',
            'comprometido' => 'integer',
        ];
    }

    public function productoTerminado(): BelongsTo
    {
        return $this->belongsTo(ProductoTerminado::class);
    }
}

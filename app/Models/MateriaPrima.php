<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MateriaPrima extends Model
{
    use HasFactory;

    protected $table = 'materias_primas';

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria',
        'unidad_base',
        'stock_minimo',
        'proveedor',
        'ubicacion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'stock_minimo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function inventario(): HasOne
    {
        return $this->hasOne(InventarioMateriaPrima::class);
    }

    public function movimientos()
    {
        return $this->morphMany(MovimientoInventario::class, 'origen');
    }

    public function recetas()
    {
        return $this->hasMany(DetalleReceta::class, 'materia_prima_id');
    }

    public function esStockBajo(): bool
    {
        return (float) $this->stock_kg() <= (float) $this->stock_minimo;
    }

    public function stock_gramos(): int
    {
        return $this->inventario?->stock_gramos ?? 0;
    }

    public function stock_kg(): float
    {
        return $this->stock_gramos() / 1000;
    }
}

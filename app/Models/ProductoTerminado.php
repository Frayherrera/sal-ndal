<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ProductoTerminado extends Model
{
    use HasFactory;

    protected $fillable = [
        'codigo',
        'nombre',
        'categoria',
        'presentacion',
        'peso_neto',
        'precio_venta',
        'stock_minimo',
        'imagen',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'peso_neto' => 'decimal:2',
            'precio_venta' => 'decimal:2',
            'stock_minimo' => 'decimal:2',
            'activo' => 'boolean',
        ];
    }

    public function inventario(): HasOne
    {
        return $this->hasOne(InventarioProductoTerminado::class);
    }

    public function movimientos()
    {
        return $this->morphMany(MovimientoInventario::class, 'origen');
    }

    public function receta(): HasMany
    {
        return $this->hasMany(DetalleReceta::class, 'producto_terminado_id');
    }

    public function tieneReceta(): bool
    {
        return (bool) $this->receta()->exists();
    }

    /**
     * Gramos de material requeridos para producir $unidades unidades.
     */
    public function gramosPorUnidad(): int
    {
        return (int) round($this->receta()->sum('gramos_por_unidad'));
    }

    public function stock_disponible(): int
    {
        return $this->inventario?->disponible ?? 0;
    }

    public function stock_comprometido(): int
    {
        return $this->inventario?->comprometido ?? 0;
    }

    public function esStockBajo(): bool
    {
        return $this->stock_disponible() <= (float) $this->stock_minimo;
    }
}

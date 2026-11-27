<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

/**
 * Producto comercial (p. ej. «COMINO MOLIDO»). Sus presentaciones son las filas
 * de producto_terminados (B2C / Especial, con su peso), y cada una es un SKU
 * con stock, receta y kardex propios.
 */
class Producto extends Model
{
    use HasFactory;

    protected $fillable = ['nombre', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function presentaciones(): HasMany
    {
        return $this->hasMany(ProductoTerminado::class)
            ->orderByRaw("CASE categoria WHEN 'B2C' THEN 0 WHEN 'ESP' THEN 1 ELSE 2 END")
            ->orderBy('peso_neto');
    }

    /** Presentaciones de una línea (B2C / ESP). */
    public function presentacionesDe(string $linea): Collection
    {
        return $this->presentaciones->where('categoria', $linea)->values();
    }

    /** Unidades disponibles en bodega de una línea, o de todas si no se indica. */
    public function stock(?string $linea = null): int
    {
        $presentaciones = $linea ? $this->presentacionesDe($linea) : $this->presentaciones;

        return (int) $presentaciones->sum(fn (ProductoTerminado $p) => $p->stock_disponible());
    }

    public function tieneStockBajo(): bool
    {
        return $this->presentaciones->contains(fn (ProductoTerminado $p) => $p->activo && $p->esStockBajo());
    }
}

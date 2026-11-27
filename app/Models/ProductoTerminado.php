<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * Presentación comercial de un Producto (línea B2C / Especial + peso). Es el SKU:
 * tiene stock, receta, kardex y movimientos del camión propios. La línea se
 * guarda en `categoria` y `nombre` se mantiene igual al del producto padre.
 */
class ProductoTerminado extends Model
{
    use HasFactory;

    /**
     * Cantidad de unidades individuales por cada paquete comercial.
     */
    public const UNIDADES_POR_PAQUETE = 20;

    /** Líneas de presentación (valor guardado en `categoria` => etiqueta). */
    public const LINEAS = ['B2C' => 'B2C', 'ESP' => 'Especial'];

    protected $fillable = [
        'producto_id',
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

    protected static function booted(): void
    {
        // Toda presentación pertenece a un producto: si no se indica, se usa
        // (o se crea) el producto con el mismo nombre.
        static::creating(function (ProductoTerminado $p) {
            if (! $p->producto_id && filled($p->nombre)) {
                $p->producto_id = Producto::firstOrCreate(['nombre' => trim($p->nombre)])->id;
            }
        });
    }

    /** Por nombre de producto, luego B2C antes que Especial y por peso. */
    public function scopeOrdenado(Builder $query): Builder
    {
        return $query->orderBy('nombre')
            ->orderByRaw("CASE categoria WHEN 'B2C' THEN 0 WHEN 'ESP' THEN 1 ELSE 2 END")
            ->orderBy('peso_neto');
    }

    public function producto(): BelongsTo
    {
        return $this->belongsTo(Producto::class);
    }

    /** «B2C» / «Especial» (o la categoría tal cual si no es una línea conocida). */
    public function linea(): string
    {
        return self::LINEAS[$this->categoria] ?? ($this->categoria ?: 'Sin línea');
    }

    /** «Especial 20 g» */
    public function etiqueta(): string
    {
        $peso = (float) $this->peso_neto;

        return trim($this->linea().($peso > 0 ? ' '.rtrim(rtrim(number_format($peso, 2, ',', '.'), '0'), ',').' g' : ''));
    }

    /** «COMINO MOLIDO · Especial 20 g» */
    public function nombreCompleto(): string
    {
        return $this->nombre.' · '.$this->etiqueta();
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

    public function precio_unitario(): float
    {
        return (float) $this->precio_venta / self::UNIDADES_POR_PAQUETE;
    }

    public function valor_total(): float
    {
        return $this->precio_unitario() * $this->stock_disponible();
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

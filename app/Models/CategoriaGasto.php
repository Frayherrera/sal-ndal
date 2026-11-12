<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaGasto extends Model
{
    use HasFactory;

    /** Categoría que reciben los gastos generados por compras de inventario. */
    public const CLAVE_COMPRA_MATERIA_PRIMA = 'compra_materia_prima';

    protected $table = 'categorias_gasto';

    protected $fillable = [
        'nombre',
        'descripcion',
        'clave',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function gastos(): HasMany
    {
        return $this->hasMany(Gasto::class);
    }

    /**
     * Las categorías con clave las usa el sistema y no se pueden eliminar.
     */
    public function esDelSistema(): bool
    {
        return $this->clave !== null;
    }

    public static function compraMateriaPrima(): self
    {
        return static::firstOrCreate(
            ['clave' => self::CLAVE_COMPRA_MATERIA_PRIMA],
            [
                'nombre' => 'Compra de materia prima',
                'descripcion' => 'Generado automáticamente al registrar compras con costo en inventario.',
                'activo' => true,
            ]
        );
    }
}

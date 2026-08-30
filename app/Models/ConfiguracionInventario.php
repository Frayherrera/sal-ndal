<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConfiguracionInventario extends Model
{
    use HasFactory;

    protected $table = 'configuracion_inventario';

    protected $fillable = [
        'clave',
        'valor',
    ];

    public static function obtener(string $clave, mixed $default = null): mixed
    {
        return static::where('clave', $clave)->value('valor') ?? $default;
    }

    public static function definir(string $clave, mixed $valor): void
    {
        static::updateOrCreate(
            ['clave' => $clave],
            ['valor' => (string) $valor]
        );
    }

    public static function permiteStockNegativo(): bool
    {
        return (bool) static::obtener('permitir_stock_negativo', false);
    }
}

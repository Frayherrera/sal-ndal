<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ViajeCamion extends Model
{
    use HasFactory;

    public const EN_RUTA = 'en_ruta';

    public const CERRADO = 'cerrado';

    public const ANULADO = 'anulado';

    public const ESTADOS = [
        self::EN_RUTA => 'En ruta',
        self::CERRADO => 'Cerrado',
        self::ANULADO => 'Anulado',
    ];

    protected $table = 'viajes_camion';

    protected $fillable = [
        'numero',
        'ruta_id',
        'fecha_salida',
        'fecha_regreso',
        'estado',
        'observaciones',
        'user_id',
        'cerrado_at',
        'cerrado_por',
        'anulado_at',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha_salida' => 'date',
            'fecha_regreso' => 'date',
            'cerrado_at' => 'datetime',
            'anulado_at' => 'datetime',
        ];
    }

    public function ruta(): BelongsTo
    {
        return $this->belongsTo(Ruta::class);
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleViajeCamion::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estaEnRuta(): bool
    {
        return $this->estado === self::EN_RUTA;
    }

    public function estaCerrado(): bool
    {
        return $this->estado === self::CERRADO;
    }
}

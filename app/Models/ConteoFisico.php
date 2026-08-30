<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConteoFisico extends Model
{
    use HasFactory;

    protected $table = 'conteos_fisicos';

    public const ESTADOS = [
        'borrador' => 'Borrador',
        'completado' => 'Completado',
        'aprobado' => 'Aprobado',
        'anulado' => 'Anulado',
    ];

    protected $fillable = [
        'codigo',
        'tipo',
        'estado',
        'fecha_conteo',
        'observaciones',
        'user_id',
        'fecha_completado',
        'fecha_aprobado',
        'fecha_anulado',
        'aprobado_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_conteo' => 'date',
            'fecha_completado' => 'datetime',
            'fecha_aprobado' => 'datetime',
            'fecha_anulado' => 'datetime',
        ];
    }

    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleConteoFisico::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }
}

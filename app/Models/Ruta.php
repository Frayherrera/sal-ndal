<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Ruta extends Model
{
    use HasFactory;

    protected $table = 'rutas';

    protected $fillable = [
        'nombre',
        'orden',
        'ciudades',
        'descripcion',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'orden' => 'integer',
            'ciudades' => 'array',
            'activo' => 'boolean',
        ];
    }

    public function viajes(): HasMany
    {
        return $this->hasMany(ViajeCamion::class);
    }

    /**
     * Ciudades normalizadas (mayúsculas, sin tildes) para cruzar con clientes.ciudad.
     *
     * @return array<int, string>
     */
    public function ciudadesNormalizadas(): array
    {
        return collect($this->ciudades ?? [])
            ->map(fn ($c) => Str::upper(Str::ascii(trim((string) $c))))
            ->filter()
            ->values()
            ->all();
    }
}

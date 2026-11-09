<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PagoVenta extends Model
{
    use HasFactory;

    public const METODOS = [
        'efectivo' => 'Efectivo',
        'transferencia' => 'Transferencia',
        'nequi' => 'Nequi',
        'daviplata' => 'Daviplata',
        'otro' => 'Otro',
    ];

    protected $table = 'pago_venta';

    protected $fillable = [
        'venta_id',
        'fecha',
        'monto',
        'metodo',
        'referencia',
        'nota',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'monto' => 'decimal:2',
        ];
    }

    public function venta(): BelongsTo
    {
        return $this->belongsTo(Venta::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

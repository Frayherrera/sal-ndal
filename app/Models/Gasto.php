<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Gasto extends Model
{
    use HasFactory;

    public const ESTADO_REGISTRADO = 'registrado';

    public const ESTADO_ANULADO = 'anulado';

    public const ESTADOS = [
        self::ESTADO_REGISTRADO => 'Registrado',
        self::ESTADO_ANULADO => 'Anulado',
    ];

    /** Mismos métodos que los abonos de ventas. */
    public const METODOS = PagoVenta::METODOS;

    protected $table = 'gastos';

    protected $fillable = [
        'numero',
        'fecha',
        'categoria_gasto_id',
        'descripcion',
        'monto',
        'metodo_pago',
        'proveedor',
        'estado',
        'movimiento_inventario_id',
        'user_id',
        'anulado_at',
        'anulado_por',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'monto' => 'decimal:2',
            'anulado_at' => 'datetime',
        ];
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaGasto::class, 'categoria_gasto_id');
    }

    public function movimiento(): BelongsTo
    {
        return $this->belongsTo(MovimientoInventario::class, 'movimiento_inventario_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anuladoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulado_por');
    }

    /**
     * Gasto creado automáticamente desde una compra de inventario.
     * No se edita ni se anula desde Gastos: se anula anulando la compra.
     */
    public function esAutomatico(): bool
    {
        return $this->movimiento_inventario_id !== null;
    }

    public function estaAnulado(): bool
    {
        return $this->estado === self::ESTADO_ANULADO;
    }

    public function metodoPago(): string
    {
        return $this->metodo_pago ? (self::METODOS[$this->metodo_pago] ?? $this->metodo_pago) : '—';
    }
}

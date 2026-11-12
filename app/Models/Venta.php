<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Venta extends Model
{
    use HasFactory;

    public const ESTADO_CONFIRMADA = 'confirmada';

    public const ESTADO_ANULADA = 'anulada';

    public const ESTADOS = [
        self::ESTADO_CONFIRMADA => 'Confirmada',
        self::ESTADO_ANULADA => 'Anulada',
    ];

    public const ESTADO_PAGO_LABELS = [
        'pagada' => 'Pagada',
        'pendiente' => 'Pendiente',
        'vencida' => 'Vencida',
        'anulada' => 'Anulada',
    ];

    public const FORMA_CONTADO = 'contado';

    public const FORMA_CREDITO = 'credito';

    public const FORMA_PAGO_LABELS = [
        self::FORMA_CONTADO => 'Contado',
        self::FORMA_CREDITO => 'Crédito',
    ];

    protected $table = 'ventas';

    protected $fillable = [
        'numero',
        'cliente_id',
        'user_id',
        'fecha',
        'subtotal',
        'descuento_total',
        'total',
        'estado',
        'forma_pago',
        'fecha_vencimiento',
        'observaciones',
        'anulada_at',
        'anulada_por',
        'motivo_anulacion',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
            'fecha_vencimiento' => 'date',
            'subtotal' => 'decimal:2',
            'descuento_total' => 'decimal:2',
            'total' => 'decimal:2',
            'anulada_at' => 'datetime',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function anuladaPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'anulada_por');
    }

    /**
     * Líneas por producto. Solo existen en ventas antiguas: las ventas nuevas
     * se registran por valor total y no tienen detalle.
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleVenta::class);
    }

    public function pagos(): HasMany
    {
        return $this->hasMany(PagoVenta::class);
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class);
    }

    /**
     * Total abonado. Usa la suma precargada (withSum('pagos', 'monto')) cuando existe.
     */
    public function totalPagado(): float
    {
        if (array_key_exists('pagos_sum_monto', $this->attributes)) {
            return round((float) $this->attributes['pagos_sum_monto'], 2);
        }

        if ($this->relationLoaded('pagos')) {
            return round((float) $this->pagos->sum('monto'), 2);
        }

        return round((float) $this->pagos()->sum('monto'), 2);
    }

    public function saldo(): float
    {
        return round((float) $this->total - $this->totalPagado(), 2);
    }

    public function estaPagada(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADA && $this->saldo() <= 0;
    }

    public function estaVencida(): bool
    {
        return $this->estado === self::ESTADO_CONFIRMADA
            && $this->saldo() > 0
            && $this->fecha_vencimiento !== null
            && $this->fecha_vencimiento->isPast();
    }

    public function estadoPago(): string
    {
        if ($this->estado === self::ESTADO_ANULADA) {
            return 'anulada';
        }

        if ($this->saldo() <= 0) {
            return 'pagada';
        }

        return $this->estaVencida() ? 'vencida' : 'pendiente';
    }

    public function formaPago(): string
    {
        $forma = $this->forma_pago ?: self::FORMA_CREDITO;

        return self::FORMA_PAGO_LABELS[$forma] ?? $forma;
    }
}

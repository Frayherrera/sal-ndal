<?php

namespace App\Services;

use App\Models\Cliente;
use App\Models\DetalleViajeCamion;
use App\Models\Gasto;
use App\Models\MovimientoInventario;
use App\Models\ProductoTerminado;
use App\Models\Ruta;
use App\Models\Venta;
use App\Models\ViajeCamion;
use App\Support\Paquetes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Cargue y descargue del camión (un solo camión, un viaje por semana).
 *
 * El camión es una segunda ubicación del producto terminado:
 *  - Surtir (bodega → camión) descuenta bodega con un movimiento `cargue_camion`.
 *  - Bajar (camión → bodega) suma a bodega con un movimiento `descargue_camion`.
 *  - Lo vendido, dañado y regalado ya salió de bodega al surtir, así que el
 *    cierre del viaje no mueve el inventario de bodega: solo calcula lo vendido.
 *
 * Todas las cantidades se manejan en UNIDADES (los formularios convierten paquetes).
 */
class ViajeCamionService
{
    /** Viajes de la misma ruta que se promedian para sugerir el surtido. */
    private const VIAJES_PARA_SUGERENCIA = 3;

    public function __construct(private MovimientoInventarioService $movimientos) {}

    public function viajeEnRuta(): ?ViajeCamion
    {
        return ViajeCamion::where('estado', ViajeCamion::EN_RUTA)->latest('id')->first();
    }

    public function ultimoCerrado(): ?ViajeCamion
    {
        return ViajeCamion::where('estado', ViajeCamion::CERRADO)
            ->orderByDesc('fecha_salida')->orderByDesc('id')->first();
    }

    /**
     * Lo que hay en el camión ahora: la carga del viaje en ruta o, si el camión está
     * en casa, lo que regresó en buen estado del último viaje cerrado.
     *
     * @return array<int, int> producto_id => unidades
     */
    public function stockCamion(): array
    {
        if ($viaje = $this->viajeEnRuta()) {
            return $viaje->detalles()->where('cargado', '>', 0)->pluck('cargado', 'producto_terminado_id')->all();
        }

        if ($viaje = $this->ultimoCerrado()) {
            return $viaje->detalles()->where('regresado', '>', 0)->pluck('regresado', 'producto_terminado_id')->all();
        }

        return [];
    }

    /**
     * La ruta que sigue en la rotación después del último viaje (o la primera).
     */
    public function rutaSugerida(): ?Ruta
    {
        $rutas = Ruta::where('activo', true)->orderBy('orden')->orderBy('id')->get();

        if ($rutas->isEmpty()) {
            return null;
        }

        $ultimo = ViajeCamion::with('ruta')->where('estado', '!=', ViajeCamion::ANULADO)
            ->orderByDesc('fecha_salida')->orderByDesc('id')->first();

        if (! $ultimo || ! $ultimo->ruta) {
            return $rutas->first();
        }

        return $rutas->first(fn (Ruta $r) => [$r->orden, $r->id] > [$ultimo->ruta->orden, $ultimo->ruta->id])
            ?? $rutas->first();
    }

    /**
     * Promedio vendido por producto en los últimos viajes cerrados de la ruta.
     *
     * @return array<int, int> producto_id => unidades (redondeado)
     */
    public function promedioVendido(Ruta $ruta): array
    {
        $viajes = ViajeCamion::where('ruta_id', $ruta->id)->where('estado', ViajeCamion::CERRADO)
            ->orderByDesc('fecha_salida')->limit(self::VIAJES_PARA_SUGERENCIA)->pluck('id');

        if ($viajes->isEmpty()) {
            return [];
        }

        return DetalleViajeCamion::whereIn('viaje_camion_id', $viajes)->get()
            ->groupBy('producto_terminado_id')
            ->map(fn (Collection $lineas) => (int) round($lineas->sum('vendido') / $viajes->count()))
            ->all();
    }

    /**
     * Carga el camión e inicia el viaje de la semana.
     *
     * @param  array{ruta_id: int, fecha_salida?: string, observaciones?: ?string, lineas?: array<int, array{surtido?: int, bajado?: int}>, user_id?: ?int}  $data
     *                                                                                                                                                       lineas: producto_id => unidades a surtir desde bodega y a bajar a bodega
     */
    public function cargar(array $data): ViajeCamion
    {
        if ($this->viajeEnRuta()) {
            throw new RuntimeException('Ya hay un viaje en ruta. Ciérralo antes de cargar el siguiente.');
        }

        $ruta = Ruta::find($data['ruta_id'] ?? null);

        if (! $ruta || ! $ruta->activo) {
            throw new RuntimeException('Selecciona una ruta activa.');
        }

        $inicial = $this->stockCamion();
        $lineas = $data['lineas'] ?? [];
        $ids = array_unique(array_merge(array_keys($inicial), array_keys($lineas)));
        $productos = ProductoTerminado::whereIn('id', $ids)->get()->keyBy('id');

        $detalles = [];

        foreach ($ids as $id) {
            $producto = $productos->get($id);

            if (! $producto) {
                throw new RuntimeException('Uno de los productos no existe.');
            }

            $enCamion = (int) ($inicial[$id] ?? 0);
            $surtido = max(0, (int) ($lineas[$id]['surtido'] ?? 0));
            $bajado = max(0, (int) ($lineas[$id]['bajado'] ?? 0));

            if ($bajado > $enCamion) {
                throw new RuntimeException("No se pueden bajar ".Paquetes::texto($bajado)." de \"{$producto->nombre}\": en el camión hay ".Paquetes::texto($enCamion).'.');
            }

            $cargado = $enCamion + $surtido - $bajado;

            if ($enCamion === 0 && $surtido === 0 && $bajado === 0) {
                continue;
            }

            $detalles[] = compact('producto', 'enCamion', 'surtido', 'bajado', 'cargado');
        }

        if (collect($detalles)->sum('cargado') <= 0) {
            throw new RuntimeException('El camión saldría vacío. Indica qué se surte.');
        }

        $fechaSalida = isset($data['fecha_salida']) ? Carbon::parse($data['fecha_salida']) : now();

        return DB::transaction(function () use ($ruta, $data, $detalles, $fechaSalida) {
            $viaje = ViajeCamion::create([
                'ruta_id' => $ruta->id,
                'fecha_salida' => $fechaSalida->toDateString(),
                'estado' => ViajeCamion::EN_RUTA,
                'observaciones' => $data['observaciones'] ?? null,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            $viaje->numero = 'VJE-'.str_pad((string) $viaje->id, 5, '0', STR_PAD_LEFT);
            $viaje->save();

            foreach ($detalles as $d) {
                $viaje->detalles()->create([
                    'producto_terminado_id' => $d['producto']->id,
                    'inicial' => $d['enCamion'],
                    'surtido' => $d['surtido'],
                    'bajado' => $d['bajado'],
                    'cargado' => $d['cargado'],
                    'precio_unidad' => round((float) $d['producto']->precio_unitario(), 2),
                ]);

                foreach (['cargue_camion' => $d['surtido'], 'descargue_camion' => $d['bajado']] as $tipo => $cantidad) {
                    if ($cantidad > 0) {
                        $this->movimientos->registrar([
                            'tipo' => $tipo,
                            'origen' => $d['producto'],
                            'cantidad' => $cantidad,
                            'documento' => $viaje->numero,
                            'referencia' => ($tipo === 'cargue_camion' ? 'Surtido al camión · ' : 'Bajado del camión · ').$ruta->nombre,
                            'viaje_camion_id' => $viaje->id,
                            'user_id' => $viaje->user_id,
                            'fecha' => now(),
                        ]);
                    }
                }
            }

            return $viaje->load('detalles.producto', 'ruta');
        });
    }

    /**
     * Cierra el viaje al regresar: registra lo que volvió, lo dañado y lo regalado,
     * y calcula lo vendido = cargado − regresado − dañado − regalado.
     *
     * @param  array{fecha_regreso?: string, lineas?: array<int, array{regresado?: int, danado?: int, regalado?: int}>, user_id?: ?int}  $data
     *                                                                                                                                  lineas: producto_id => unidades
     */
    public function cerrar(ViajeCamion $viaje, array $data): ViajeCamion
    {
        if (! $viaje->estaEnRuta()) {
            throw new RuntimeException('Solo se puede cerrar un viaje que está en ruta.');
        }

        $fechaRegreso = isset($data['fecha_regreso']) ? Carbon::parse($data['fecha_regreso']) : now();

        if ($fechaRegreso->lt($viaje->fecha_salida)) {
            throw new RuntimeException('La fecha de regreso no puede ser anterior a la salida.');
        }

        $lineas = $data['lineas'] ?? [];
        $viaje->load('detalles.producto');
        $calculos = [];

        foreach ($viaje->detalles as $detalle) {
            $linea = $lineas[$detalle->producto_terminado_id] ?? [];
            $regresado = max(0, (int) ($linea['regresado'] ?? 0));
            $danado = max(0, (int) ($linea['danado'] ?? 0));
            $regalado = max(0, (int) ($linea['regalado'] ?? 0));
            $vendido = $detalle->cargado - $regresado - $danado - $regalado;

            if ($vendido < 0) {
                throw new RuntimeException("En \"{$detalle->producto?->nombre}\" lo que regresó, se dañó o se regaló ("
                    .Paquetes::texto($regresado + $danado + $regalado).') supera lo que salió ('.Paquetes::texto($detalle->cargado).').');
            }

            $calculos[] = [$detalle, compact('regresado', 'danado', 'regalado', 'vendido')];
        }

        return DB::transaction(function () use ($viaje, $calculos, $fechaRegreso, $data) {
            foreach ($calculos as [$detalle, $valores]) {
                $detalle->update($valores);
            }

            $viaje->update([
                'estado' => ViajeCamion::CERRADO,
                'fecha_regreso' => $fechaRegreso->toDateString(),
                'cerrado_at' => now(),
                'cerrado_por' => $data['user_id'] ?? auth()->id(),
            ]);

            return $viaje->fresh(['detalles.producto', 'ruta']);
        });
    }

    /**
     * Anula un viaje en ruta: revierte el surtido y lo bajado (vuelve todo a como estaba).
     */
    public function anular(ViajeCamion $viaje, string $motivo): ViajeCamion
    {
        if (! $viaje->estaEnRuta()) {
            throw new RuntimeException('Solo se puede anular un viaje que está en ruta (sin cerrar).');
        }

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException('Indica el motivo de la anulación.');
        }

        return DB::transaction(function () use ($viaje, $motivo) {
            $viaje->movimientos()->whereNull('movimiento_original_id')
                ->whereIn('tipo', MovimientoInventario::TIPOS_AUTOMATICOS)
                ->get()
                ->each(fn (MovimientoInventario $m) => $this->movimientos->anular($m, 'Viaje anulado: '.$motivo));

            $viaje->update([
                'estado' => ViajeCamion::ANULADO,
                'anulado_at' => now(),
                'motivo_anulacion' => mb_substr($motivo, 0, 500),
            ]);

            return $viaje->fresh();
        });
    }

    /**
     * Resultado económico del viaje.
     *
     * @return array{vendido: float, merma: float, ventas_registradas: float, ventas_count: int, diferencia: float, gastos_ruta: float, resultado: float, filtra_por_ciudad: bool}
     */
    public function resultado(ViajeCamion $viaje): array
    {
        $viaje->loadMissing('detalles', 'ruta');

        $desde = $viaje->fecha_salida->toDateString();
        $hasta = ($viaje->fecha_regreso ?? now())->toDateString();
        $ciudades = $viaje->ruta?->ciudadesNormalizadas() ?? [];

        $ventas = Venta::where('estado', Venta::ESTADO_CONFIRMADA)
            ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta);

        if ($ciudades !== []) {
            $clientes = Cliente::all()
                ->filter(fn (Cliente $c) => in_array(Str::upper(Str::ascii(trim((string) $c->ciudad))), $ciudades, true))
                ->pluck('id');
            $ventas->whereIn('cliente_id', $clientes);
        }

        $ventasRegistradas = round((float) (clone $ventas)->sum('total'), 2);

        $gastosRuta = round((float) Gasto::where('estado', Gasto::ESTADO_REGISTRADO)
            ->whereHas('categoria', fn ($q) => $q->where('nombre', 'Ruta'))
            ->whereDate('fecha', '>=', $desde)->whereDate('fecha', '<=', $hasta)
            ->sum('monto'), 2);

        $vendido = round($viaje->detalles->sum(fn (DetalleViajeCamion $d) => $d->valorVendido()), 2);

        return [
            'vendido' => $vendido,
            'merma' => round($viaje->detalles->sum(fn (DetalleViajeCamion $d) => $d->valorMerma()), 2),
            'ventas_registradas' => $ventasRegistradas,
            'ventas_count' => (clone $ventas)->count(),
            'diferencia' => round($ventasRegistradas - $vendido, 2),
            'gastos_ruta' => $gastosRuta,
            'resultado' => round($ventasRegistradas - $gastosRuta, 2),
            'filtra_por_ciudad' => $ciudades !== [],
        ];
    }
}

<?php

namespace App\Services\Telegram;

use App\Models\DetalleViajeCamion;
use App\Models\ProductoTerminado;
use App\Models\ViajeCamion;
use App\Services\Telegram\TelegramFormato as F;
use App\Services\ViajeCamionService;
use App\Support\Paquetes;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Consultas del módulo Camión para el bot (solo lectura). Devuelven HTML de Telegram.
 * El cargue y el regreso se registran en la aplicación web.
 */
class TelegramCamion
{
    /** Productos que se listan como máximo en un mensaje. */
    private const MAX_PRODUCTOS = 25;

    public function __construct(private ViajeCamionService $camion) {}

    /** /camion — dónde está el camión y qué lleva. */
    public function estado(): string
    {
        $enRuta = $this->camion->viajeEnRuta();

        if ($enRuta) {
            $enRuta->load('ruta', 'detalles.producto');
            $lineas = [
                '<b>🚚 El camión está en ruta</b>',
                'Ruta: <b>'.F::e($enRuta->ruta?->nombre).'</b> · <code>'.$enRuta->numero.'</code>',
                'Salió el '.$enRuta->fecha_salida->format('d/m/Y').' con <b>'.Paquetes::texto($enRuta->detalles->sum('cargado')).'</b>',
                '',
            ];

            return implode("\n", array_merge($lineas, $this->listaProductos(
                $enRuta->detalles->mapWithKeys(fn (DetalleViajeCamion $d) => [$d->producto_terminado_id => $d->cargado])->all()
            ), ['', 'Al regresar, registra lo que volvió en la app: Camión → Registrar regreso.']));
        }

        $stock = $this->camion->stockCamion();
        $sugerida = $this->camion->rutaSugerida();

        $lineas = [
            '<b>🏠 El camión está en casa</b>',
            $stock === []
                ? 'Está vacío.'
                : 'Tiene <b>'.Paquetes::texto(array_sum($stock)).'</b> que regresaron del último viaje:',
        ];

        if ($stock !== []) {
            $lineas[] = '';
            $lineas = array_merge($lineas, $this->listaProductos($stock));
        }

        if ($sugerida) {
            $lineas[] = '';
            $lineas[] = 'Siguiente ruta en la rotación: <b>'.F::e($sugerida->nombre).'</b>.';
        }

        return implode("\n", $lineas);
    }

    /**
     * /vendido [viaje] — lo vendido en un viaje cerrado (por defecto el último).
     * Acepta el número del viaje: "VJE-00003", "3".
     */
    public function vendido(string $viajeTexto = ''): string
    {
        $viaje = $this->buscarViajeCerrado($viajeTexto);
        $viaje->load('ruta', 'detalles.producto');
        $r = $this->camion->resultado($viaje);

        $vendidos = $viaje->detalles->filter(fn (DetalleViajeCamion $d) => $d->vendido > 0)
            ->sortByDesc('vendido')->values();

        $lineas = [
            '<b>📦 Vendido en '.$viaje->numero.'</b> · '.F::e($viaje->ruta?->nombre),
            $viaje->fecha_salida->format('d/m').' al '.$viaje->fecha_regreso?->format('d/m/Y'),
            '',
        ];

        foreach ($vendidos->take(self::MAX_PRODUCTOS) as $d) {
            $lineas[] = '• '.F::e($d->producto?->nombre).': <b>'.Paquetes::texto($d->vendido).'</b> · '.F::dinero($d->valorVendido());
        }

        if ($vendidos->count() > self::MAX_PRODUCTOS) {
            $lineas[] = '… y '.($vendidos->count() - self::MAX_PRODUCTOS).' productos más (detalle en la app).';
        }

        if ($vendidos->isEmpty()) {
            $lineas[] = 'No se vendió nada en este viaje.';
        }

        $danado = (int) $viaje->detalles->sum('danado');
        $regalado = (int) $viaje->detalles->sum('regalado');

        return implode("\n", array_merge($lineas, [
            '',
            'Total vendido: <b>'.Paquetes::texto((int) $viaje->detalles->sum('vendido')).'</b> · <b>'.F::dinero($r['vendido']).'</b> a precio de catálogo',
            'Merma: '.Paquetes::texto($danado).' dañado, '.Paquetes::texto($regalado).' regalado ('.F::dinero($r['merma']).')',
            'Ventas registradas: '.F::dinero($r['ventas_registradas']).' ('.$r['ventas_count'].' ventas'
                .($r['filtra_por_ciudad'] ? ' a clientes de la ruta' : '').')'
                .(abs($r['diferencia']) >= 1 ? ' · diferencia '.F::dinero($r['diferencia']) : ' · cuadra con el camión'),
            'Gastos de ruta: '.F::dinero($r['gastos_ruta']).' · Ventas − gastos: <b>'.F::dinero($r['resultado']).'</b>',
        ]));
    }

    /**
     * /semanas [producto] — lo vendido en los últimos viajes cerrados.
     * Sin producto: total por viaje. Con producto: ese producto viaje por viaje.
     */
    public function semanas(string $producto = '', int $cantidad = 6): string
    {
        $cantidad = max(1, min($cantidad, 12));

        $viajes = ViajeCamion::with('ruta', 'detalles.producto')
            ->where('estado', ViajeCamion::CERRADO)
            ->orderByDesc('fecha_salida')->orderByDesc('id')
            ->limit($cantidad)->get()->reverse()->values();

        if ($viajes->isEmpty()) {
            return 'Todavía no hay viajes cerrados.';
        }

        $producto = trim($producto);

        if ($producto === '') {
            $lineas = ["<b>📊 Vendido en los últimos {$viajes->count()} viajes</b>", ''];

            foreach ($viajes as $v) {
                $valor = $v->detalles->sum(fn (DetalleViajeCamion $d) => $d->valorVendido());
                $lineas[] = "<code>{$v->numero}</code> · {$v->fecha_salida->format('d/m')} · ".F::e($v->ruta?->nombre)
                    .': <b>'.Paquetes::texto((int) $v->detalles->sum('vendido')).'</b> · '.F::dinero($valor);
            }

            $lineas[] = '';
            $lineas[] = 'Un producto semana a semana: <code>/semanas comino</code>';

            return implode("\n", $lineas);
        }

        $termino = F::normalizar($producto);
        $productos = $viajes->flatMap->detalles->pluck('producto')->filter()
            ->unique('id')
            ->filter(fn (ProductoTerminado $p) => str_contains(F::normalizar($p->nombre), $termino))
            ->sortBy('nombre')->values();

        if ($productos->isEmpty()) {
            throw new RuntimeException('Ningún producto con «'.F::e($producto).'» salió en el camión en esos viajes.');
        }

        $lineas = ['<b>📊 «'.F::e($producto).'» por viaje</b>'];

        foreach ($productos->take(8) as $p) {
            $lineas[] = '';
            $lineas[] = '<b>'.F::e($p->nombre).'</b>';
            $total = 0;

            foreach ($viajes as $v) {
                $d = $v->detalles->firstWhere('producto_terminado_id', $p->id);
                $vendido = (int) ($d?->vendido ?? 0);
                $total += $vendido;
                $lineas[] = "  {$v->fecha_salida->format('d/m')} ".F::e($v->ruta?->nombre).': '.($d ? Paquetes::texto($vendido) : '—');
            }

            $lineas[] = '  Total: <b>'.Paquetes::texto($total).'</b>';
        }

        if ($productos->count() > 8) {
            $lineas[] = '';
            $lineas[] = 'Hay más productos con ese nombre; escribe uno más específico.';
        }

        return implode("\n", $lineas);
    }

    /**
     * @param  array<int, int>  $unidades  producto_id => unidades
     * @return array<int, string>
     */
    private function listaProductos(array $unidades): array
    {
        $unidades = array_filter($unidades, fn ($u) => $u > 0);
        arsort($unidades);

        $nombres = ProductoTerminado::whereIn('id', array_keys($unidades))->pluck('nombre', 'id');
        $lineas = [];

        foreach (array_slice($unidades, 0, self::MAX_PRODUCTOS, true) as $id => $u) {
            $lineas[] = '• '.F::e($nombres[$id] ?? '—').': '.Paquetes::texto($u);
        }

        if (count($unidades) > self::MAX_PRODUCTOS) {
            $lineas[] = '… y '.(count($unidades) - self::MAX_PRODUCTOS).' productos más.';
        }

        return $lineas;
    }

    private function buscarViajeCerrado(string $texto): ViajeCamion
    {
        $texto = trim($texto);

        if ($texto === '') {
            return $this->camion->ultimoCerrado()
                ?? throw new RuntimeException('Todavía no hay viajes cerrados.');
        }

        if (! preg_match('/(\d+)/', $texto, $m)) {
            throw new RuntimeException('Indica el número del viaje. Ej: <code>/vendido VJE-00003</code> o <code>/vendido 3</code>');
        }

        $viaje = ViajeCamion::where('numero', 'VJE-'.str_pad($m[1], 5, '0', STR_PAD_LEFT))->first();

        if (! $viaje) {
            throw new RuntimeException('No encontré el viaje '.F::e($texto).'.');
        }

        if (! $viaje->estaCerrado()) {
            throw new RuntimeException("El viaje {$viaje->numero} todavía no está cerrado"
                .($viaje->estaEnRuta() ? ' (sigue en ruta). Usa /camion para ver qué lleva.' : '.'));
        }

        return $viaje;
    }
}

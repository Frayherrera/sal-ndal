<?php

namespace App\Services\Telegram;

use App\Models\Cliente;
use App\Models\Gasto;
use App\Models\MateriaPrima;
use App\Models\ProductoTerminado;
use App\Models\Venta;
use App\Services\InventarioService;
use App\Services\Telegram\TelegramFormato as F;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Respuestas de solo lectura del bot. Cada método devuelve el texto (HTML) a enviar.
 */
class TelegramConsultas
{
    public function __construct(
        private InventarioService $inventario,
        private BuscadorTelegram $buscador,
    ) {}

    public function resumen(): string
    {
        $r = $this->inventario->resumen();

        return implode("\n", [
            '<b>📊 Resumen — '.now()->format('d/m/Y').'</b>',
            '',
            '<b>Hoy</b>',
            '• Vendido: '.F::dinero($r['ventas_hoy_total'])." ({$r['ventas_hoy_count']} ventas)",
            '• Gastado: '.F::dinero($r['gastos_hoy_total'])." ({$r['gastos_hoy_count']} gastos)",
            '',
            '<b>Este mes</b>',
            '• Vendido: '.F::dinero($r['ventas_mes_total'])." ({$r['ventas_mes_count']} ventas)",
            '• Gastado: '.F::dinero($r['gastos_mes_total'])." ({$r['gastos_mes_count']} gastos)",
            '• Ventas − gastos: <b>'.F::dinero($r['resultado_mes']).'</b>',
            '',
            '<b>Cartera</b>',
            '• Saldo pendiente: '.F::dinero($r['cartera_pendiente']),
            '• Ventas vencidas: '.$r['cartera_vencidas'],
        ]);
    }

    public function cartera(string $cliente = ''): string
    {
        if (trim($cliente) !== '') {
            return $this->carteraDeCliente($this->buscador->cliente($cliente));
        }

        $pendientes = $this->ventasConSaldo();

        if ($pendientes->isEmpty()) {
            return '✅ No hay saldos pendientes en cartera.';
        }

        $porCliente = $pendientes
            ->groupBy('cliente_id')
            ->map(fn (Collection $ventas) => [
                'cliente' => $ventas->first()->cliente?->nombre ?? '—',
                'saldo' => $ventas->sum(fn (Venta $v) => $v->saldo()),
                'ventas' => $ventas->count(),
                'vencidas' => $ventas->filter(fn (Venta $v) => $v->estaVencida())->count(),
            ])
            ->sortByDesc('saldo');

        $lineas = [
            '<b>💼 Cartera</b>',
            'Saldo total: <b>'.F::dinero($pendientes->sum(fn (Venta $v) => $v->saldo())).'</b>',
            $pendientes->count().' ventas con saldo · '.$pendientes->filter(fn (Venta $v) => $v->estaVencida())->count().' vencidas',
            '',
        ];

        foreach ($porCliente->take(15) as $fila) {
            $lineas[] = '• '.F::e($fila['cliente']).': <b>'.F::dinero($fila['saldo']).'</b>'
                .($fila['vencidas'] > 0 ? " ⚠️ {$fila['vencidas']} vencida(s)" : '');
        }

        if ($porCliente->count() > 15) {
            $lineas[] = '… y '.($porCliente->count() - 15).' clientes más.';
        }

        $lineas[] = '';
        $lineas[] = 'Detalle de un cliente: <code>/cartera nombre</code>';

        return implode("\n", $lineas);
    }

    public function stock(string $texto): string
    {
        $texto = trim($texto);

        if (mb_strlen($texto) < 2) {
            throw new RuntimeException('Escribe parte del nombre. Ej: <code>/stock comino</code>');
        }

        $termino = '%'.$texto.'%';

        $materias = MateriaPrima::with('inventario')->where('activo', true)
            ->where('nombre', 'like', $termino)->orderBy('nombre')->limit(15)->get();
        $productos = ProductoTerminado::with('inventario')->where('activo', true)
            ->where('nombre', 'like', $termino)->orderBy('nombre')->limit(15)->get();

        if ($materias->isEmpty() && $productos->isEmpty()) {
            return 'No encontré materias primas ni productos con «'.F::e($texto).'».';
        }

        $lineas = ['<b>📦 Stock: «'.F::e($texto).'»</b>'];

        if ($materias->isNotEmpty()) {
            $lineas[] = '';
            $lineas[] = '<b>Materia prima</b>';
            foreach ($materias as $mp) {
                $lineas[] = '• '.F::e($mp->nombre).': '.$this->kg($mp->stock_kg()).' kg'.($mp->esStockBajo() ? ' ⚠️ bajo' : '');
            }
        }

        if ($productos->isNotEmpty()) {
            $lineas[] = '';
            $lineas[] = '<b>Producto terminado</b>';
            foreach ($productos as $pt) {
                $lineas[] = '• '.F::e($pt->nombre).($pt->presentacion ? ' ('.F::e($pt->presentacion).')' : '')
                    .': '.number_format($pt->stock_disponible(), 0, ',', '.').' u'.($pt->esStockBajo() ? ' ⚠️ bajo' : '');
            }
        }

        return implode("\n", $lineas);
    }

    public function alertas(): string
    {
        $alertas = $this->inventario->alertasStockBajo();
        $materias = $alertas['materias_primas'];
        $productos = $alertas['productos'];

        if ($materias->isEmpty() && $productos->isEmpty()) {
            return '✅ No hay artículos con stock bajo.';
        }

        $lineas = ['<b>⚠️ Stock bajo</b>'];

        if ($materias->isNotEmpty()) {
            $lineas[] = '';
            $lineas[] = '<b>Materia prima</b> ('.$materias->count().')';
            foreach ($materias->take(25) as $mp) {
                $lineas[] = '• '.F::e($mp->nombre).': '.$this->kg($mp->stock_kg()).' kg (mín. '.$this->kg((float) $mp->stock_minimo).')';
            }
        }

        if ($productos->isNotEmpty()) {
            $lineas[] = '';
            $lineas[] = '<b>Producto terminado</b> ('.$productos->count().')';
            foreach ($productos->take(25) as $pt) {
                $lineas[] = '• '.F::e($pt->nombre).': '.$pt->stock_disponible().' u (mín. '.(int) $pt->stock_minimo.')';
            }
        }

        return implode("\n", $lineas);
    }

    public function ventas(int $cantidad = 10): string
    {
        $cantidad = max(1, min($cantidad, 30));

        $ventas = Venta::with('cliente')->withSum('pagos', 'monto')
            ->orderByDesc('fecha')->orderByDesc('id')->limit($cantidad)->get();

        if ($ventas->isEmpty()) {
            return 'Todavía no hay ventas registradas.';
        }

        $lineas = ["<b>🧾 Últimas {$ventas->count()} ventas</b>", ''];

        foreach ($ventas as $v) {
            $estado = Venta::ESTADO_PAGO_LABELS[$v->estadoPago()] ?? $v->estadoPago();
            $lineas[] = "<code>{$v->numero}</code> · {$v->fecha->format('d/m')} · ".F::e($v->cliente?->nombre ?? '—')
                .' · <b>'.F::dinero($v->total).'</b> · '.$v->formaPago().' · '.$estado
                .($v->saldo() > 0 && $v->estado === Venta::ESTADO_CONFIRMADA ? ' (debe '.F::dinero($v->saldo()).')' : '');
        }

        return implode("\n", $lineas);
    }

    public function gastos(int $cantidad = 10): string
    {
        $cantidad = max(1, min($cantidad, 30));

        $gastos = Gasto::with('categoria')->where('estado', Gasto::ESTADO_REGISTRADO)
            ->orderByDesc('fecha')->orderByDesc('id')->limit($cantidad)->get();

        if ($gastos->isEmpty()) {
            return 'Todavía no hay gastos registrados.';
        }

        $totalMes = (float) Gasto::where('estado', Gasto::ESTADO_REGISTRADO)
            ->whereYear('fecha', now()->year)->whereMonth('fecha', now()->month)->sum('monto');

        $lineas = ["<b>💸 Últimos {$gastos->count()} gastos</b>", ''];

        foreach ($gastos as $g) {
            $lineas[] = "<code>{$g->numero}</code> · {$g->fecha->format('d/m')} · ".F::e($g->descripcion)
                .' · '.F::e($g->categoria?->nombre ?? '—').' · <b>'.F::dinero($g->monto).'</b>';
        }

        $lineas[] = '';
        $lineas[] = 'Gastado este mes: <b>'.F::dinero($totalMes).'</b>';

        return implode("\n", $lineas);
    }

    /**
     * Ventas confirmadas con saldo pendiente, de la más antigua a la más reciente.
     *
     * @return Collection<int, Venta>
     */
    public function ventasConSaldo(?Cliente $cliente = null): Collection
    {
        return Venta::with('cliente')
            ->withSum('pagos', 'monto')
            ->where('estado', Venta::ESTADO_CONFIRMADA)
            ->when($cliente, fn ($q) => $q->where('cliente_id', $cliente->id))
            ->orderBy('fecha')
            ->orderBy('id')
            ->get()
            ->filter(fn (Venta $v) => $v->saldo() > 0)
            ->values();
    }

    private function carteraDeCliente(Cliente $cliente): string
    {
        $ventas = $this->ventasConSaldo($cliente);
        $titulo = '<b>💼 '.F::e($cliente->nombre).'</b>'.($cliente->local ? ' · '.F::e($cliente->local) : '');

        if ($ventas->isEmpty()) {
            return $titulo."\n✅ No tiene saldo pendiente.";
        }

        $lineas = [$titulo, 'Debe: <b>'.F::dinero($ventas->sum(fn (Venta $v) => $v->saldo())).'</b>', ''];

        foreach ($ventas as $v) {
            $lineas[] = "<code>{$v->numero}</code> · {$v->fecha->format('d/m/Y')} · total ".F::dinero($v->total)
                .' · saldo <b>'.F::dinero($v->saldo()).'</b>'
                .($v->fecha_vencimiento ? ' · vence '.$v->fecha_vencimiento->format('d/m') : '')
                .($v->estaVencida() ? ' ⚠️' : '');
        }

        return implode("\n", $lineas);
    }

    private function kg(float $kg): string
    {
        return rtrim(rtrim(number_format($kg, 3, ',', '.'), '0'), ',');
    }
}

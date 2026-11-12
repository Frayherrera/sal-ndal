<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\InventarioProductoTerminado;
use App\Models\MovimientoInventario;
use App\Models\PagoVenta;
use App\Models\ProductoTerminado;
use App\Models\User;
use App\Models\Venta;
use App\Services\MovimientoInventarioService;
use App\Services\PagoVentaService;
use App\Services\VentaService;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use RuntimeException;
use Tests\TestCase;

class VentaServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    private VentaService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->create());
        $this->service = app(VentaService::class);
    }

    /**
     * @param  array<string, mixed>  $extra
     */
    private function crearVenta(Cliente $cliente, float $total = 50000, array $extra = []): Venta
    {
        return $this->service->crear(array_merge([
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'forma_pago' => 'credito',
            'abono_inicial' => 0,
            'total' => $total,
        ], $extra));
    }

    #[Test]
    public function crea_la_venta_por_valor_total_y_genera_consecutivo(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 50000, ['observaciones' => 'Pedido surtido']);

        $this->assertSame('VTA-00001', $venta->numero);
        $this->assertSame(50000.0, (float) $venta->total);
        $this->assertSame(50000.0, (float) $venta->subtotal);
        $this->assertSame(0.0, (float) $venta->descuento_total);
        $this->assertSame('Pedido surtido', $venta->observaciones);
        $this->assertSame(0, $venta->detalles()->count());
    }

    #[Test]
    public function no_toca_el_inventario(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 100,
            'comprometido' => 0,
        ]);

        $venta = $this->crearVenta($cliente);

        $this->assertSame(100, $pt->fresh()->inventario->disponible);
        $this->assertSame(0, MovimientoInventario::count());
        $this->assertDatabaseMissing('movimientos_inventario', ['venta_id' => $venta->id]);
    }

    #[Test]
    public function rechaza_total_cero_o_negativo(): void
    {
        $cliente = Cliente::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/mayor a cero/');

        $this->crearVenta($cliente, 0);
    }

    #[Test]
    public function rechaza_venta_sin_cliente(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Debes seleccionar un cliente.');

        $this->service->crear([
            'cliente_id' => null,
            'fecha' => now()->toDateString(),
            'total' => 1000,
        ]);
    }

    #[Test]
    public function exige_fecha_de_vencimiento_cuando_queda_saldo(): void
    {
        $cliente = Cliente::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/fecha de vencimiento/');

        $this->crearVenta($cliente, 1200, ['fecha_vencimiento' => null]);
    }

    #[Test]
    public function no_exige_vencimiento_si_la_venta_queda_pagada(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 1200, [
            'abono_inicial' => 1200,
            'fecha_vencimiento' => null,
        ]);

        $this->assertNull($venta->fecha_vencimiento);
        $this->assertSame('pagada', $venta->estadoPago());
    }

    #[Test]
    public function la_venta_de_contado_se_paga_en_su_totalidad(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 3600, [
            'forma_pago' => 'contado',
            'abono_inicial' => 0,
        ]);

        $this->assertSame(3600.0, (float) $venta->total);
        $this->assertSame(3600.0, $venta->totalPagado());
        $this->assertSame(0.0, $venta->saldo());
        $this->assertNull($venta->fecha_vencimiento);
        $this->assertSame('pagada', $venta->estadoPago());
        $this->assertSame('Contado', $venta->formaPago());
        $this->assertSame('contado', $venta->forma_pago);
        $this->assertSame(1, $venta->pagos()->count());
        $this->assertSame(3600.0, (float) $venta->pagos()->first()->monto);
    }

    #[Test]
    public function la_venta_a_credito_conserva_su_forma_de_pago_aunque_se_pague(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 1200);

        $this->assertSame('Crédito', $venta->formaPago());
        $this->assertSame('pendiente', $venta->estadoPago());
        $this->assertSame(1200.0, $venta->saldo());

        $venta->pagos()->create([
            'fecha' => now(),
            'monto' => 1200,
            'metodo' => 'efectivo',
            'user_id' => $venta->user_id,
        ]);

        $venta = $venta->fresh();

        $this->assertSame('pagada', $venta->estadoPago());
        $this->assertSame('Crédito', $venta->formaPago());
    }

    #[Test]
    public function registra_el_abono_inicial_como_pago(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 2400, ['abono_inicial' => 1000, 'metodo_pago' => 'transferencia']);

        $pago = $venta->pagos()->first();

        $this->assertNotNull($pago);
        $this->assertSame(1000.0, (float) $pago->monto);
        $this->assertSame('transferencia', $pago->metodo);
        $this->assertSame('Abono inicial', $pago->nota);
        $this->assertSame(1400.0, $venta->saldo());
        $this->assertSame('pendiente', $venta->estadoPago());
    }

    #[Test]
    public function rechaza_abono_inicial_mayor_al_total(): void
    {
        $cliente = Cliente::factory()->create();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/no puede superar el total/');

        $this->crearVenta($cliente, 1200, ['abono_inicial' => 50000]);
    }

    #[Test]
    public function anular_marca_la_venta(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente);

        $anulada = $this->service->anular($venta, 'Error de digitación');

        $this->assertSame(Venta::ESTADO_ANULADA, $anulada->estado);
        $this->assertSame('Error de digitación', $anulada->motivo_anulacion);
        $this->assertNotNull($anulada->anulada_at);
        $this->assertSame('anulada', $venta->fresh()->estadoPago());
    }

    #[Test]
    public function anular_una_venta_antigua_por_productos_revierte_su_stock(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = ProductoTerminado::factory()->create();
        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => 100,
            'comprometido' => 0,
        ]);

        $venta = $this->crearVenta($cliente, 2400);

        // Simula una venta registrada con el modelo anterior (con despacho de stock)
        app(MovimientoInventarioService::class)->registrar([
            'tipo' => 'venta_despacho',
            'origen' => $pt,
            'cantidad' => 2,
            'documento' => $venta->numero,
            'fecha' => $venta->fecha,
            'venta_id' => $venta->id,
        ]);

        $this->assertSame(98, $pt->fresh()->inventario->disponible);

        $this->service->anular($venta, 'Cliente desistió');

        $this->assertSame(100, $pt->fresh()->inventario->disponible);
        $this->assertDatabaseHas('movimientos_inventario', [
            'venta_id' => $venta->id,
            'tipo' => 'anulacion_reversion',
            'direccion' => 'entrada',
            'cantidad' => 2,
        ]);
    }

    #[Test]
    public function no_permite_anular_una_venta_con_abonos(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 2400, ['abono_inicial' => 1000]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/abonos registrados/');

        $this->service->anular($venta, 'Devolución');
    }

    #[Test]
    public function no_permite_anular_dos_veces(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente);

        $this->service->anular($venta, 'Primera anulación');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ya está anulada/');

        $this->service->anular($venta->fresh(), 'Segunda anulación');
    }

    #[Test]
    public function el_servicio_de_pagos_rechaza_abonos_que_superan_el_saldo(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 1200, ['abono_inicial' => 1000]);

        $this->expectException(RuntimeException::class);

        app(PagoVentaService::class)->registrar($venta->fresh(), ['monto' => 50000, 'metodo' => 'efectivo']);
    }

    #[Test]
    public function el_servicio_de_pagos_registra_un_abono_valido(): void
    {
        $cliente = Cliente::factory()->create();

        $venta = $this->crearVenta($cliente, 1200);

        $pago = app(PagoVentaService::class)
            ->registrar($venta->fresh(), ['monto' => 1200, 'metodo' => 'nequi']);

        $this->assertInstanceOf(PagoVenta::class, $pago);
        $this->assertSame('pagada', $venta->fresh()->estadoPago());
    }
}

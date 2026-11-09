<?php

namespace Tests\Unit;

use App\Models\Cliente;
use App\Models\InventarioProductoTerminado;
use App\Models\PagoVenta;
use App\Models\ProductoTerminado;
use App\Models\User;
use App\Models\Venta;
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

    private function producto(int $stock = 100, float $precio = 24000): ProductoTerminado
    {
        $pt = ProductoTerminado::factory()->create([
            'precio_venta' => $precio,
            'activo' => true,
        ]);

        InventarioProductoTerminado::create([
            'producto_terminado_id' => $pt->id,
            'disponible' => $stock,
            'comprometido' => 0,
        ]);

        return $pt;
    }

    /**
     * @param  array<int, array<string, mixed>>  $lineas
     * @param  array<string, mixed>  $extra
     */
    private function crearVenta(Cliente $cliente, array $lineas, array $extra = []): Venta
    {
        return $this->service->crear(array_merge([
            'cliente_id' => $cliente->id,
            'fecha' => now()->toDateString(),
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
            'abono_inicial' => 0,
            'lineas' => $lineas,
        ], $extra));
    }

    #[Test]
    public function crea_la_venta_descuenta_stock_en_unidades_y_genera_consecutivo(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(100);

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 2,
            'descuento_tipo' => 'monto',
            'descuento_valor' => 0,
        ]]);

        $this->assertSame('VTA-00001', $venta->numero);
        $this->assertSame(2400.0, (float) $venta->total);
        $this->assertSame(0.0, (float) $venta->descuento_total);
        $this->assertSame(98, $pt->fresh()->inventario->disponible);
        $this->assertDatabaseHas('movimientos_inventario', [
            'venta_id' => $venta->id,
            'tipo' => 'venta_despacho',
            'cantidad' => 2,
            'direccion' => 'salida',
        ]);
        $this->assertSame(1, $venta->detalles()->count());
    }

    #[Test]
    public function congela_el_precio_del_producto_en_la_linea(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(100, 24000);

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]]);

        $pt->update(['precio_venta' => 99000]);

        $detalle = $venta->fresh()->detalles->first();

        $this->assertSame(1200.0, (float) $detalle->precio_unidad);
        $this->assertSame(1200.0, (float) $detalle->total);
    }

    #[Test]
    public function aplica_descuento_por_porcentaje(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(100, 24000);

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 2,
            'descuento_tipo' => 'porcentaje',
            'descuento_valor' => 10,
        ]]);

        $this->assertSame(240.0, (float) $venta->descuento_total);
        $this->assertSame(2160.0, (float) $venta->total);
    }

    #[Test]
    public function aplica_descuento_por_monto_con_tope_en_el_bruto(): void
    {
        $this->assertSame(5000.0, $this->service->calcularDescuento(24000, 'monto', 5000));
        $this->assertSame(24000.0, $this->service->calcularDescuento(24000, 'monto', 999999));
        $this->assertSame(2400.0, $this->service->calcularDescuento(24000, 'porcentaje', 10));
    }

    #[Test]
    public function rechaza_descuento_porcentual_mayor_a_cien(): void
    {
        $this->expectException(RuntimeException::class);

        $this->service->calcularDescuento(24000, 'porcentaje', 120);
    }

    #[Test]
    public function suma_unidades_de_lineas_duplicadas_para_validar_stock(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(30); // alcanza para 20 u, no para 40 u

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stock insuficiente/');

        $this->crearVenta($cliente, [
            ['producto_terminado_id' => $pt->id, 'cantidad_unidades' => 20],
            ['producto_terminado_id' => $pt->id, 'cantidad_unidades' => 20],
        ]);
    }

    #[Test]
    public function rechaza_venta_sin_cliente(): void
    {
        $pt = $this->producto();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Debes seleccionar un cliente.');

        $this->service->crear([
            'cliente_id' => null,
            'fecha' => now()->toDateString(),
            'lineas' => [['producto_terminado_id' => $pt->id, 'cantidad_unidades' => 1]],
        ]);
    }

    #[Test]
    public function exige_fecha_de_vencimiento_cuando_queda_saldo(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/fecha de vencimiento/');

        $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]], ['fecha_vencimiento' => null]);
    }

    #[Test]
    public function no_exige_vencimiento_si_la_venta_queda_pagada(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto();

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]], [
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
        $pt = $this->producto(100, 24000); // 1200 por unidad

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 3,
        ]], [
            'forma_pago' => 'contado',
            'abono_inicial' => 0,
            'fecha_vencimiento' => now()->addDays(30)->toDateString(),
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
        $pt = $this->producto();

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]], [
            'forma_pago' => 'credito',
            'abono_inicial' => 0,
        ]);

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
        $pt = $this->producto();

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 2,
        ]], ['abono_inicial' => 1000, 'metodo_pago' => 'transferencia']);

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
        $pt = $this->producto();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/no puede superar el total/');

        $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]], ['abono_inicial' => 50000]);
    }

    #[Test]
    public function anular_revierte_el_stock_y_marca_la_venta(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto(100);

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 2,
        ]]);

        $this->assertSame(98, $pt->fresh()->inventario->disponible);

        $anulada = $this->service->anular($venta, 'Error de digitación');

        $this->assertSame(Venta::ESTADO_ANULADA, $anulada->estado);
        $this->assertSame('Error de digitación', $anulada->motivo_anulacion);
        $this->assertSame(100, $pt->fresh()->inventario->disponible);
        $this->assertDatabaseHas('movimientos_inventario', [
            'venta_id' => $venta->id,
            'tipo' => 'anulacion_reversion',
            'direccion' => 'entrada',
            'cantidad' => 2,
        ]);
        $this->assertSame('anulada', $venta->fresh()->estadoPago());
    }

    #[Test]
    public function no_permite_anular_una_venta_con_abonos(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto();

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 2,
        ]], ['abono_inicial' => 1000]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/abonos registrados/');

        $this->service->anular($venta, 'Devolución');
    }

    #[Test]
    public function no_permite_anular_dos_veces(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto();

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]]);

        $this->service->anular($venta, 'Primera anulación');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessageMatches('/ya está anulada/');

        $this->service->anular($venta->fresh(), 'Segunda anulación');
    }

    #[Test]
    public function el_servicio_de_pagos_rechaza_abonos_que_superan_el_saldo(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto();

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]], ['abono_inicial' => 1000]);

        $servicioPagos = app(\App\Services\PagoVentaService::class);

        $this->expectException(RuntimeException::class);

        $servicioPagos->registrar($venta->fresh(), ['monto' => 50000, 'metodo' => 'efectivo']);
    }

    #[Test]
    public function el_servicio_de_pagos_registra_un_abono_valido(): void
    {
        $cliente = Cliente::factory()->create();
        $pt = $this->producto();

        $venta = $this->crearVenta($cliente, [[
            'producto_terminado_id' => $pt->id,
            'cantidad_unidades' => 1,
        ]], ['abono_inicial' => 0]);

        $pago = app(\App\Services\PagoVentaService::class)
            ->registrar($venta->fresh(), ['monto' => 1200, 'metodo' => 'nequi']);

        $this->assertInstanceOf(PagoVenta::class, $pago);
        $this->assertSame('pagada', $venta->fresh()->estadoPago());
    }
}

<?php

namespace App\Services;

use App\Models\CategoriaGasto;
use App\Models\Gasto;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class GastoService
{
    /**
     * Registra un gasto manual.
     *
     * @param  array<string, mixed>  $data
     */
    public function crear(array $data): Gasto
    {
        $limpios = $this->normalizar($data);

        return DB::transaction(function () use ($limpios, $data) {
            $gasto = Gasto::create($limpios + [
                'estado' => Gasto::ESTADO_REGISTRADO,
                'user_id' => $data['user_id'] ?? auth()->id(),
            ]);

            return $this->asignarNumero($gasto);
        });
    }

    /**
     * Actualiza un gasto manual que no esté anulado.
     *
     * @param  array<string, mixed>  $data
     */
    public function actualizar(Gasto $gasto, array $data): Gasto
    {
        $this->asegurarEditable($gasto);

        $gasto->update($this->normalizar($data, $gasto));

        return $gasto->fresh(['categoria']);
    }

    /**
     * Anula un gasto manual. El gasto no se borra: queda marcado como anulado.
     */
    public function anular(Gasto $gasto, string $motivo): Gasto
    {
        $this->asegurarEditable($gasto);

        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new RuntimeException('Debes indicar el motivo de la anulación.');
        }

        return $this->marcarAnulado($gasto, $motivo);
    }

    /**
     * Crea el gasto automático de una compra de inventario con costo.
     * Devuelve null si el movimiento no es una compra o no tiene costo.
     */
    public function registrarDesdeCompra(MovimientoInventario $movimiento): ?Gasto
    {
        $monto = round((float) $movimiento->costo_total, 2);

        if ($movimiento->tipo !== 'compra_recepcion' || $monto <= 0) {
            return null;
        }

        $origen = $movimiento->origen;
        $nombre = $origen?->nombre ?? 'materia prima';
        $cantidad = $origen instanceof MateriaPrima
            ? rtrim(rtrim(number_format($movimiento->cantidad / 1000, 3, ',', '.'), '0'), ',').' kg'
            : $movimiento->cantidad.' u';

        $descripcion = "Compra de {$nombre} ({$cantidad})";

        if ($movimiento->documento) {
            $descripcion .= ' · Doc. '.$movimiento->documento;
        }

        $gasto = Gasto::create([
            'fecha' => Carbon::parse($movimiento->fecha)->toDateString(),
            'categoria_gasto_id' => CategoriaGasto::compraMateriaPrima()->id,
            'descripcion' => mb_substr($descripcion, 0, 255),
            'monto' => $monto,
            'metodo_pago' => null,
            'proveedor' => $origen instanceof MateriaPrima ? $origen->proveedor : null,
            'estado' => Gasto::ESTADO_REGISTRADO,
            'movimiento_inventario_id' => $movimiento->id,
            'user_id' => $movimiento->user_id,
        ]);

        return $this->asignarNumero($gasto);
    }

    /**
     * Anula el gasto automático asociado a una compra que se está anulando.
     */
    public function anularPorMovimiento(MovimientoInventario $movimiento, ?string $motivo = null): void
    {
        $gasto = Gasto::where('movimiento_inventario_id', $movimiento->id)
            ->where('estado', Gasto::ESTADO_REGISTRADO)
            ->first();

        if ($gasto) {
            $this->marcarAnulado($gasto, $motivo ?: 'Compra anulada en inventario');
        }
    }

    private function marcarAnulado(Gasto $gasto, string $motivo): Gasto
    {
        $gasto->update([
            'estado' => Gasto::ESTADO_ANULADO,
            'anulado_at' => now(),
            'anulado_por' => auth()->id(),
            'motivo_anulacion' => mb_substr($motivo, 0, 500),
        ]);

        return $gasto->fresh();
    }

    private function asegurarEditable(Gasto $gasto): void
    {
        if ($gasto->estaAnulado()) {
            throw new RuntimeException('El gasto ya está anulado.');
        }

        if ($gasto->esAutomatico()) {
            throw new RuntimeException('Este gasto se generó desde una compra de inventario. Para corregirlo, anula la compra en Movimientos.');
        }
    }

    private function asignarNumero(Gasto $gasto): Gasto
    {
        $gasto->numero = 'GTO-'.str_pad((string) $gasto->id, 5, '0', STR_PAD_LEFT);
        $gasto->save();

        return $gasto->fresh(['categoria']);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizar(array $data, ?Gasto $actual = null): array
    {
        $categoria = CategoriaGasto::find($data['categoria_gasto_id'] ?? null);

        if (! $categoria) {
            throw new RuntimeException('Selecciona una categoría.');
        }

        // Una categoría inactiva solo se acepta si el gasto ya la tenía
        if (! $categoria->activo && $actual?->categoria_gasto_id !== $categoria->id) {
            throw new RuntimeException("La categoría \"{$categoria->nombre}\" está inactiva.");
        }

        $monto = round((float) ($data['monto'] ?? 0), 2);

        if ($monto <= 0) {
            throw new RuntimeException('El valor del gasto debe ser mayor a cero.');
        }

        $descripcion = trim((string) ($data['descripcion'] ?? ''));

        if ($descripcion === '') {
            throw new RuntimeException('Describe el gasto.');
        }

        $metodo = $data['metodo_pago'] ?? null;

        if ($metodo !== null && $metodo !== '' && ! array_key_exists($metodo, Gasto::METODOS)) {
            throw new RuntimeException('Método de pago no válido.');
        }

        $proveedor = trim((string) ($data['proveedor'] ?? ''));

        return [
            'fecha' => isset($data['fecha']) ? Carbon::parse($data['fecha'])->toDateString() : now()->toDateString(),
            'categoria_gasto_id' => $categoria->id,
            'descripcion' => $descripcion,
            'monto' => $monto,
            'metodo_pago' => $metodo ?: null,
            'proveedor' => $proveedor !== '' ? $proveedor : null,
        ];
    }
}

<?php

namespace App\Services;

use App\Models\DetalleMolido;
use App\Models\MateriaPrima;
use App\Models\MovimientoInventario;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MolidoService
{
    public function __construct(protected MovimientoInventarioService $movimientoService) {}

    /**
     * Guarda (crea o reemplaza) los ingredientes de un molido.
     *
     * @param  array<int, array{ingrediente_id?: int, gramos_por_kg?: mixed}>  $lineas
     */
    public function guardarIngredientes(MateriaPrima $molido, array $lineas): MateriaPrima
    {
        $limpias = $this->normalizarLineas($molido, $lineas);

        return DB::transaction(function () use ($molido, $limpias) {
            $molido->detalleMolido()->delete();

            foreach ($limpias as $linea) {
                DetalleMolido::create([
                    'molido_id' => $molido->id,
                    'ingrediente_id' => $linea['ingrediente_id'],
                    'gramos_por_kg' => $linea['gramos_por_kg'],
                ]);
            }

            return $molido->load('detalleMolido.ingrediente');
        });
    }

    public function eliminarIngredientes(MateriaPrima $molido): void
    {
        $molido->detalleMolido()->delete();
    }

    /**
     * Materias primas que pueden usarse como ingredientes de un molido.
     * Incluye las inactivas que ya forman parte de su receta para no perderlas al editar.
     *
     * @return Collection<int, MateriaPrima>
     */
    public function ingredientesDisponibles(?MateriaPrima $molido = null): Collection
    {
        $query = MateriaPrima::query()
            ->where(function ($q) use ($molido) {
                $q->where('activo', true);
                if ($molido && $molido->exists) {
                    $q->orWhereIn('id', $molido->detalleMolido()->pluck('ingrediente_id'));
                }
            })
            ->orderBy('nombre');

        if ($molido && $molido->exists) {
            $query->whereKeyNot($molido->getKey());
        }

        return $query->get();
    }

    /**
     * Produce $gramos de un molido consumiendo sus ingredientes según la receta.
     *
     * @param  array<string, mixed>  $data
     * @return array<int, MovimientoInventario>
     */
    public function producir(MateriaPrima $molido, int $gramos, array $data = []): array
    {
        return $this->movimientoService->producirMolido($molido, $gramos, $data);
    }

    /**
     * Filtra líneas vacías, valida duplicados/auto-consumo y normaliza valores.
     *
     * @param  array<int, array{ingrediente_id?: int, gramos_por_kg?: mixed}>  $lineas
     * @return array<int, array{ingrediente_id: int, gramos_por_kg: float}>
     */
    private function normalizarLineas(MateriaPrima $molido, array $lineas): array
    {
        $resultado = [];
        $vistos = [];

        foreach ($lineas as $linea) {
            $ingId = (int) ($linea['ingrediente_id'] ?? 0);
            $gramos = (float) ($linea['gramos_por_kg'] ?? 0);

            if ($ingId <= 0) {
                continue;
            }

            if ($gramos <= 0) {
                throw new RuntimeException('La cantidad (gramos por kg) debe ser mayor a cero.');
            }

            if ($molido->exists && $ingId === $molido->id) {
                throw new RuntimeException('Un molido no puede usar su propio producto como ingrediente.');
            }

            if (isset($vistos[$ingId])) {
                throw new RuntimeException('Un ingrediente no puede repetirse dos veces en la misma molienda.');
            }

            $vistos[$ingId] = true;
            $resultado[] = [
                'ingrediente_id' => $ingId,
                'gramos_por_kg' => $gramos,
            ];
        }

        if (empty($resultado)) {
            throw new RuntimeException('Debe agregar al menos un ingrediente al molido.');
        }

        return $resultado;
    }
}

<?php

namespace App\Services\Telegram;

use App\Models\CategoriaGasto;
use App\Models\Cliente;
use App\Services\Telegram\TelegramFormato as F;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Busca clientes y categorías por nombre escrito a mano en el chat
 * (sin distinguir mayúsculas, tildes ni espacios extra).
 */
class BuscadorTelegram
{
    public function cliente(string $texto): Cliente
    {
        /** @var Cliente */
        return $this->unico(
            Cliente::orderBy('nombre')->get(),
            $texto,
            fn (Cliente $c) => array_filter([$c->nombre, $c->local]),
            fn (Cliente $c) => $c->nombre.($c->local ? " ({$c->local})" : ''),
            'cliente'
        );
    }

    /**
     * Categoría activa para gastos manuales (excluye la automática de compras).
     */
    public function categoria(string $texto): CategoriaGasto
    {
        /** @var CategoriaGasto */
        return $this->unico(
            CategoriaGasto::where('activo', true)->whereNull('clave')->orderBy('nombre')->get(),
            $texto,
            fn (CategoriaGasto $c) => [$c->nombre],
            fn (CategoriaGasto $c) => $c->nombre,
            'categoría'
        );
    }

    /**
     * @param  Collection<int, Model>  $candidatos
     */
    private function unico(Collection $candidatos, string $texto, callable $nombres, callable $etiqueta, string $que): Model
    {
        $buscado = F::normalizar($texto);

        if ($buscado === '') {
            throw new RuntimeException($que === 'categoría' ? 'Indica la categoría.' : 'Indica el cliente.');
        }

        $coincide = fn (callable $prueba) => $candidatos->filter(
            fn (Model $m) => collect($nombres($m))->contains(fn (string $n) => $prueba(F::normalizar($n)))
        )->values();

        // 1) Nombre exacto  2) Empieza por el texto  3) Contiene el texto
        foreach ([
            fn (string $n) => $n === $buscado,
            fn (string $n) => str_starts_with($n, $buscado),
            fn (string $n) => str_contains($n, $buscado),
        ] as $prueba) {
            $encontrados = $coincide($prueba);

            if ($encontrados->count() === 1) {
                return $encontrados->first();
            }

            if ($encontrados->count() > 1) {
                $opciones = $encontrados->take(8)->map(fn (Model $m) => '• '.F::e($etiqueta($m)))->implode("\n");

                throw new RuntimeException(($que === 'categoría' ? 'Hay varias categorías' : 'Hay varios clientes')
                    .' que coinciden con «'.F::e($texto)."». Escribe el nombre más completo:\n".$opciones);
            }
        }

        $sugerencia = $que === 'categoría'
            ? "\nCategorías disponibles:\n".$candidatos->map(fn (Model $m) => '• '.F::e($etiqueta($m)))->implode("\n")
            : '';

        throw new RuntimeException(($que === 'categoría' ? 'No encontré ninguna categoría' : 'No encontré ningún cliente').' «'.F::e($texto).'».'.$sugerencia);
    }
}

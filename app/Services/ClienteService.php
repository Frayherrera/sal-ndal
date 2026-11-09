<?php

namespace App\Services;

use App\Models\Cliente;

class ClienteService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function crear(array $data): Cliente
    {
        return Cliente::create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function actualizar(Cliente $cliente, array $data): Cliente
    {
        $cliente->update($data);

        return $cliente;
    }

    public function eliminar(Cliente $cliente): void
    {
        $cliente->delete();
    }
}

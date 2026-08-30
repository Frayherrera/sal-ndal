<?php

namespace App\Http\Controllers;

use App\Services\InventarioService;
use Illuminate\View\View;

class InventarioController extends Controller
{
    public function __construct(protected InventarioService $service) {}

    public function dashboard(): View
    {
        $resumen = $this->service->resumen();

        return view('inventario.dashboard', $resumen);
    }

    public function alertas(): View
    {
        $alertas = $this->service->alertasStockBajo();

        return view('inventario.alertas', $alertas);
    }
}

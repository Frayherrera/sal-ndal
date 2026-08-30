<?php

use App\Http\Controllers\ConteoFisicoController;
use App\Http\Controllers\InventarioController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MateriaPrimaController;
use App\Http\Controllers\MovimientoInventarioController;
use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\ProductoTerminadoController;
use App\Http\Controllers\RecetaController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
})->name('welcome');

// Rutas de autenticación
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

// Rutas protegidas — Módulo Inventario
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/gestion', [InventarioController::class, 'dashboard'])->name('gestion');

    // Alertas de stock bajo
    Route::get('/inventario/alertas', [InventarioController::class, 'alertas'])->name('inventario.alertas');

    // Materias primas
    Route::resource('inventario/materia-prima', MateriaPrimaController::class)
        ->parameters(['materia-prima' => 'materia'])
        ->names('inventario.materia-prima');
    Route::post('inventario/materia-prima/{materia}/toggle', [MateriaPrimaController::class, 'toggleActivo'])
        ->name('inventario.materia-prima.toggle');

    // Productos terminados
    Route::resource('inventario/productos-terminados', ProductoTerminadoController::class)
        ->parameters(['productos-terminados' => 'producto'])
        ->names('inventario.productos-terminados');
    Route::post('inventario/productos-terminados/{producto}/toggle', [ProductoTerminadoController::class, 'toggleActivo'])
        ->name('inventario.productos-terminados.toggle');

    // Movimientos / Kardex
    Route::get('inventario/movimientos', [MovimientoInventarioController::class, 'index'])
        ->name('inventario.movimientos.index');
    Route::get('inventario/movimientos/crear', [MovimientoInventarioController::class, 'create'])
        ->name('inventario.movimientos.create');
    Route::post('inventario/movimientos', [MovimientoInventarioController::class, 'store'])
        ->name('inventario.movimientos.store');
    Route::get('inventario/movimientos/{movimiento}', [MovimientoInventarioController::class, 'show'])
        ->name('inventario.movimientos.show');
    Route::get('inventario/movimientos/{movimiento}/anular', [MovimientoInventarioController::class, 'anularView'])
        ->name('inventario.movimientos.anular');
    Route::post('inventario/movimientos/{movimiento}/anular', [MovimientoInventarioController::class, 'anular'])
        ->name('inventario.movimientos.anular.store');

    // Conteo físico
    Route::get('inventario/conteo-fisico', [ConteoFisicoController::class, 'index'])
        ->name('inventario.conteo-fisico.index');
    Route::get('inventario/conteo-fisico/crear', [ConteoFisicoController::class, 'create'])
        ->name('inventario.conteo-fisico.create');
    Route::post('inventario/conteo-fisico', [ConteoFisicoController::class, 'store'])
        ->name('inventario.conteo-fisico.store');
    Route::get('inventario/conteo-fisico/{conteo}', [ConteoFisicoController::class, 'show'])
        ->name('inventario.conteo-fisico.show');
    Route::post('inventario/conteo-fisico/{conteo}/detalles', [ConteoFisicoController::class, 'registrarDetalle'])
        ->name('inventario.conteo-fisico.detalles');
    Route::post('inventario/conteo-fisico/{conteo}/completar', [ConteoFisicoController::class, 'completar'])
        ->name('inventario.conteo-fisico.completar');
    Route::post('inventario/conteo-fisico/{conteo}/aprobar', [ConteoFisicoController::class, 'aprobar'])
        ->name('inventario.conteo-fisico.aprobar');
    Route::post('inventario/conteo-fisico/{conteo}/anular', [ConteoFisicoController::class, 'anular'])
        ->name('inventario.conteo-fisico.anular');

    // Recetas (BOM)
    Route::get('inventario/recetas', [RecetaController::class, 'index'])
        ->name('inventario.recetas.index');
    Route::get('inventario/recetas/crear', [RecetaController::class, 'create'])
        ->name('inventario.recetas.create');
    Route::post('inventario/recetas', [RecetaController::class, 'store'])
        ->name('inventario.recetas.store');
    Route::get('inventario/recetas/{producto}/editar', [RecetaController::class, 'edit'])
        ->name('inventario.recetas.edit');
    Route::put('inventario/recetas/{producto}', [RecetaController::class, 'update'])
        ->name('inventario.recetas.update');
    Route::delete('inventario/recetas/{producto}', [RecetaController::class, 'destroy'])
        ->name('inventario.recetas.destroy');

    // Producción
    Route::get('inventario/produccion/crear', [ProduccionController::class, 'create'])
        ->name('inventario.produccion.create');
    Route::post('inventario/produccion', [ProduccionController::class, 'store'])
        ->name('inventario.produccion.store');
});

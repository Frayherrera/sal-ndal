<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('auth.login');
});

// Rutas de autenticación
Route::get('/login', [LoginController::class, 'show'])->name('login');
Route::post('/login', [LoginController::class, 'authenticate']);
Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/debug-user', function () {
    $user = \App\Models\User::where('email', 'admin@admin.co')->first();
    if (!$user) return 'Usuario no encontrado';
    $plainPassword = env('ADMIN_PASSWORD', 'password');
    $matches = \Illuminate\Support\Facades\Hash::check($plainPassword, $user->password);
    return [
        'email' => $user->email,
        'hash' => $user->password,
        'plain' => $plainPassword,
        'matches' => $matches,
    ];
});

// Rutas protegidas
Route::middleware('auth')->group(function () {
    Route::get('/gestion', function () {
        return view('gestion');
    })->name('gestion');
});

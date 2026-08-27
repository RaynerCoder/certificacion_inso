<?php

use App\Http\Controllers\Auth\LoginCaptchaController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/menu');

// Limita la regeneracion de imagenes sin afectar los intentos normales de acceso.
Route::get('/seguridad/captcha-login', LoginCaptchaController::class)
    ->middleware(['guest', 'throttle:30,1'])
    ->name('captcha.login');

// Vista publica del frontend. Los territorios se leen del catalogo existente;
// el formulario todavia no guarda preinscripciones porque no existe esa tabla.
Route::get('/capacitaciones/preinscripcion', function () {
    $territorios = \App\Models\Territorio::query()
        ->where('estado', 'ACTIVO')
        ->orderBy('nombre')
        ->get(['id', 'nombre']);

    return view('capacitaciones.publico.preinscripcion', compact('territorios'));
})->name('capacitaciones_preinscripcion_publica');

/* Route::get('/', function () {
    return view('welcome');
}); */

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', function () {
        return redirect()->route('admin_dashboard');
    })->name('dashboard');
});

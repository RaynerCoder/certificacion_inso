<?php

// use App\Http\Controllers\Auth\LoginCaptchaController;


// Importa el sistema de rutas de Laravel.
use Illuminate\Support\Facades\Route;

// Importa Socialite para comunicarse con Ciudadanía Digital mediante OIDC.
//use Laravel\Socialite\Facades\Socialite;

// Controla el inicio de sesión mediante Ciudadanía Digital.
use App\Http\Controllers\Auth\CiudadaniaDigitalController;
use App\Http\Controllers\CertificadoController;


// Cuando se visita la raíz del sistema, redirige al menú.
Route::redirect('/', '/menu');



/*
// Crea la dirección que inicia el acceso con Ciudadanía Digital.
Route::get('/auth/ciudadania-digital/redirect', function () {
    
    // Usa la configuración "ciudadania_digital" y redirige hacia AGETIC.
    return Socialite::driver('oidc_ciudadania_digital')->redirect();

// Permite esta ruta solo a usuarios sin sesión y le asigna un nombre interno.
})->middleware('guest')->name('ciudadania-digital.redirect');
*/



// Envía al ciudadano hacia la autenticación de AGETIC.
Route::get('/auth/ciudadania-digital/redirect',[CiudadaniaDigitalController::class, 'redirigir'])->middleware('guest')->name('ciudadania-digital.redirect');

// Recibe la respuesta de AGETIC después de autenticar al ciudadano.
Route::get('/auth/ciudadania-digital/callback',[CiudadaniaDigitalController::class, 'callback'])->middleware('guest')->name('ciudadania-digital.callback');

// Inicia el cierre de sesión en Ciudadanía Digital.
Route::post('/auth/ciudadania-digital/logout',[CiudadaniaDigitalController::class, 'cerrarSesion'])->middleware('auth')->name('ciudadania-digital.logout');

// Recibe al usuario después de cerrar la sesión en AGETIC.
Route::get('/auth/ciudadania-digital/logout/callback',[CiudadaniaDigitalController::class, 'retornoCerrarSesion'])->name('ciudadania-digital.logout.callback');

// Consulta pública y de solo lectura. El QR de cada emisión dirige a esta ruta.
Route::get('/verificar/{codigoVerificacion}', [CertificadoController::class, 'verificarPublico'])
    ->whereUuid('codigoVerificacion')
    ->name('certificados_verificar_publico');




/*
| CAPTCHA del inicio de sesión deshabilitado temporalmente.
| Descomente también el controlador importado arriba para volver a utilizarlo.
|
| Route::get('/seguridad/captcha-login', LoginCaptchaController::class)
|     ->middleware(['guest', 'throttle:30,1'])
|     ->name('captcha.login');
*/

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

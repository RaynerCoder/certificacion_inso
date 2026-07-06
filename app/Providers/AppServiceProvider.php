<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Directiva Blade para mostrar u ocultar partes de la vista segun permisos dinamicos.
        // Uso: @permiso('productos.ver') ... @endpermiso
        Blade::if('permiso', function (string|array $permisos): bool {
            $usuario = auth()->user();

            return $usuario && $usuario->puede($permisos);
        });
    }
}

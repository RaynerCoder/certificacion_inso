<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarAccesoSistema
{
    public const MENSAJE = 'No se pudo ingresar al sistema. Comuníquese con INSO.';

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();
        if (!$usuario || $usuario->tieneAccesoSistema()) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->withErrors(['email' => self::MENSAJE]);
    }
}

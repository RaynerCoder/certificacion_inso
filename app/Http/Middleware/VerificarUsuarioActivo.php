<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class VerificarUsuarioActivo
{
    /**
     * Cierra la sesion si la cuenta fue inactivada mientras estaba conectada.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        if (! $usuario) {
            return $next($request);
        }

        // Consulta el estado actual para no confiar en datos antiguos guardados en la sesion.
        $usuarioActivo = $usuario->newQuery()
            ->whereKey($usuario->getAuthIdentifier())
            ->where('estado', 1)
            ->exists();

        if ($usuarioActivo) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->withErrors([
                'email' => 'Su cuenta se encuentra inactiva. Comuníquese con un administrador.',
            ]);
    }
}

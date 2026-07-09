<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    /**
     * Valida permisos dinamicos antes de entrar a una ruta.
     * Si la ruta no declara permiso, se intenta resolver por el prefijo del nombre de ruta.
     */
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        $usuario = $request->user();
        $permisos = $permisos ?: $this->permisosPorRuta($request->route()?->getName());

        if (!$usuario) {
            abort(403, 'No tiene permiso para acceder a esta seccion.');
        }

        if ($permisos && !$usuario->puede($permisos)) {
            abort(403, 'No tiene permiso para acceder a esta seccion.');
        }

        return $next($request);
    }

    private function permisosPorRuta(?string $ruta): array
    {
        if (!$ruta) {
            return [];
        }

        $rutasExactas = [
            'admin_dashboard' => 'dashboard.ver',
            'notificaciones_tramites' => 'dashboard.ver',
            'notificaciones_tramites_leer' => 'dashboard.ver',
            'notificaciones_tramites_leer_todas' => 'dashboard.ver',
            // El detalle del certificado tambien se abre desde "Mis tramites".
            // El controlador valida despues que el tramite pertenezca al usuario.
            'certificados_show' => ['certificados.ver', 'seguimientos_tramite.ver'],
            // La vista de impresión se abre desde "Mis trámites"; el controlador valida que sea propio.
            'certificados_emitir' => ['certificados.emitir', 'seguimientos_tramite.enviados'],
        ];

        if (isset($rutasExactas[$ruta])) {
            return (array) $rutasExactas[$ruta];
        }

        foreach ($this->permisosPorPrefijo() as $prefijo => $permiso) {
            if (str_starts_with($ruta, $prefijo)) {
                return [$permiso];
            }
        }

        return [];
    }

    private function permisosPorPrefijo(): array
    {
        return [
            'territorios_' => 'territorios.ver',
            'personas_naturales_' => 'personas_naturales.ver',
            'personas_' => 'personas.ver',
            'empresas_' => 'empresas.ver',
            'tipos_empresas_' => 'tipos_empresas.ver',
            'responsables_' => 'responsables.ver',
            'tramitadores_' => 'tramitadores.ver',
            'rubros_' => 'rubros.ver',
            'tipos_certificados_' => 'tipos_certificados.ver',
            'requisitos_' => 'requisitos.ver',
            'certificados_plantillas_' => 'plantillas_certificados.ver',
            'certificados_emitir' => 'certificados.emitir',
            'certificados_' => 'certificados.ver',
            'fabricantes_' => 'fabricantes.ver',
            'tipos_productos_' => 'tipos_productos.ver',
            'ingredientes_' => 'ingredientes.ver',
            'productos_' => 'productos.ver',
            'presentaciones_' => 'presentaciones.ver',
            'registros_' => 'registros.ver',
            'usuarios_' => 'usuarios.ver',
            'roles_' => 'roles.gestionar',
            'permisos_' => 'permisos.ver',
            'areas_' => 'areas.ver',
            'cargos_' => 'cargos.ver',
            'procedencias_' => 'procedencias.ver',
            'pagos_' => 'pagos.ver',
            'tipos_evidencias_' => 'tipos_evidencias.ver',
        ];
    }
}

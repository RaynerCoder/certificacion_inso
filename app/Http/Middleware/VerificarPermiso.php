<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerificarPermiso
{
    // Verifica si el usuario tiene permiso para acceder a la ruta.
    public function handle(Request $request, Closure $next, string ...$permisos): Response
    {
        // Obtiene el usuario que inició sesión.
        $usuario = $request->user();

        // Si no se indicó un permiso en la ruta,
        // busca automáticamente qué permiso necesita según el nombre de la ruta.
        $permisos = $permisos ?: $this->permisosPorRuta(
            $request->route()?->getName()
        );

        // Si no existe un usuario autenticado, bloquea el acceso.
        if (!$usuario) {
            abort(403, 'No tiene permiso para acceder a esta seccion.');
        }

        // Comprueba si el usuario tiene alguno de los permisos requeridos.
        // El método puede() es el que realmente revisa los permisos del usuario.
        if ($permisos && !$usuario->puede($permisos)) {
            abort(403, 'No tiene permiso para acceder a esta seccion.');
        }

        // Si tiene permiso, permite continuar al controlador.
        return $next($request);
    }


    // Obtiene los permisos necesarios según el nombre de la ruta.
    private function permisosPorRuta(?string $ruta): array
    {
        // Si la ruta no tiene nombre, no hay permiso que buscar.
        if (!$ruta) {
            return [];
        }

        // Permisos especiales para rutas que pueden ser usadas
        // por usuarios con diferentes permisos.
        $rutasExactas = [

            // Acceso al panel principal.
            'admin_dashboard' => 'dashboard.ver',

            // Las notificaciones se consultan desde el panel principal.
            'notificaciones_tramites' => 'dashboard.ver',
            'notificaciones_tramites_leer' => 'dashboard.ver',
            'notificaciones_tramites_leer_todas' => 'dashboard.ver',

            // Acceso al módulo de reportes.
            'reportes_index' => 'reportes.ver',

            // Puede acceder quien pueda ver o validar tramitadores.
            'tramitadores_index' => [
                'tramitadores.ver',
                'tramitadores.validar'
            ],

            'tramitadores_show' => [
                'tramitadores.ver',
                'tramitadores.validar'
            ],

            'tramitadores_carta' => [
                'tramitadores.ver',
                'tramitadores.validar'
            ],

            // Para editar se necesita permiso de validación.
            'tramitadores_edit' => 'tramitadores.validar',
            'tramitadores_update' => 'tramitadores.validar',


            // Puede ver el certificado quien tenga alguno
            // de estos permisos relacionados con certificados o trámites.
            'certificados_show' => [
                'certificados.ver',
                'seguimientos_tramite.ver',
                'seguimientos_tramite.enviados',
                'seguimientos_tramite.registrados',
                'seguimientos_tramite.atender',
                'seguimientos_tramite.gestionar',
                'seguimientos_tramite.consulta_general',
                'seguimientos_tramite.historial',
            ],

            // Permisos que permiten emitir un certificado.
            'certificados_emitir' => [
                'certificados.emitir',
                'seguimientos_tramite.enviados'
            ],

            // Permisos que permiten descargar un certificado.
            'certificados_descargar' => [
                'certificados.emitir',
                'seguimientos_tramite.enviados'
            ],


            // Puede consultar el comprobante quien tenga
            // algún permiso relacionado con pagos o trámites.
            'pagos_comprobante' => [
                'pagos.ver',
                'pagos.validar',
                'certificados.ver',
                'seguimientos_tramite.ver',
                'seguimientos_tramite.enviados',
                'seguimientos_tramite.registrados',
                'seguimientos_tramite.atender',
                'seguimientos_tramite.gestionar',
                'seguimientos_tramite.consulta_general',
            ],


            // Puede entrar al listado quien gestione o configure certificados.
            'tipos_certificados_index' => [
                'tipos_certificados.gestionar',
                'tipos_certificados.configurar',
            ],

            'tipos_certificados_show' => [
                'tipos_certificados.gestionar',
                'tipos_certificados.configurar',
            ],

            // Permiso para crear o modificar datos básicos.
            'tipos_certificados_store' =>
                'tipos_certificados.gestionar',

            'tipos_certificados_update_basico' =>
                'tipos_certificados.gestionar',

            // Permiso para modificar la configuración.
            'tipos_certificados_configuracion' =>
                'tipos_certificados.configurar',

            'tipos_certificados_configuracion_update' =>
                'tipos_certificados.configurar',


            // Permisos que permiten ver y administrar el QR del pago.
            'pagos_openbcb_mostrar_pago' => [
                'pagos.ver',
                'pagos.validar',
                'seguimientos_tramite.iniciar',
                'seguimientos_tramite.enviados',
            ],

            'pagos_openbcb_generar_qr' => [
                'pagos.ver',
                'pagos.validar',
                'seguimientos_tramite.iniciar',
                'seguimientos_tramite.enviados',
            ],

            'pagos_openbcb_consultar_qr' => [
                'pagos.ver',
                'pagos.validar',
                'seguimientos_tramite.iniciar',
                'seguimientos_tramite.enviados',
            ],

            // Permite mostrar la imagen del QR del trámite.
            // El controlador comprueba que pertenezca al usuario.
            'pagos_openbcb_mostrar_imagen_qr' => [
                'pagos.ver',
                'pagos.validar',
                'seguimientos_tramite.iniciar',
                'seguimientos_tramite.enviados',
            ],            
        ];


        // Primero busca si la ruta tiene permisos especiales.
        if (isset($rutasExactas[$ruta])) {
            return (array) $rutasExactas[$ruta];
        }


        // Si no es una ruta especial, busca el permiso por su prefijo.
        //
        // Ejemplo:
        // usuarios_index empieza con usuarios_
        // entonces necesita usuarios.ver
        foreach ($this->permisosPorPrefijo() as $prefijo => $permiso) {
            if (str_starts_with($ruta, $prefijo)) {
                return [$permiso];
            }
        }

        // Si no encuentra coincidencia, no asigna permisos.
        return [];
    }


    // Relaciona el inicio del nombre de una ruta con su permiso.
    //
    // Ejemplo:
    // usuarios_index  -> usuarios.ver
    // empresas_index  -> empresas.ver
    // productos_show  -> productos.ver
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
            'tipos_certificados_' => 'tipos_certificados.gestionar',
            'requisitos_' => 'requisitos.ver',
            'certificados_plantillas_' => 'plantillas_certificados.ver',
            'certificados_emitir' => 'certificados.emitir',
            'certificados_' => 'certificados.ver',
            'fabricantes_' => 'fabricantes.ver',
            'clasificaciones_toxicologicas_' => 'clasificaciones_toxicologicas.ver',
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
            'reportes_' => 'reportes.ver',
        ];
    }
}
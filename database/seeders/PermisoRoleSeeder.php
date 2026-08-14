<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisoRoleSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Asignacion de permisos a roles.
     */
    public function run(): void
    {
        $permisoValidarTramitadores = DB::table('permisos')
            ->where('nombre', 'tramitadores.validar')
            ->value('id');

        if (! $permisoValidarTramitadores) {
            throw new \RuntimeException('Primero debe registrarse el permiso tramitadores.validar.');
        }

        // Estos roles describen la relacion con una empresa; el acceso se concede con Solicitante.
        $rolesDeRelacion = DB::table('roles')
            ->whereIn('slug', ['representante-legal', 'tramitador'])
            ->pluck('id');
        DB::table('permisos_roles')->whereIn('id_role', $rolesDeRelacion)->delete();

        $permisosAdministrador = DB::table('permisos')
            ->where('estado', 1)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $permisosPorRol = [
            // El administrador recibe todos los permisos activos, incluidos los que se agreguen más adelante.
            1 => $permisosAdministrador,

            // Funcionario: atiende trámites, consulta información técnica y ve lo que registró en ventanilla.
            2 => [
                1, 2, 3, 4, 5, 6, 10, 11, 12,
                18, 19, 21, 22, 23, $permisoValidarTramitadores,
                24, 25, 26, 27, 28,
                29, 30, 31, 32, 33, 34, 35, 37, 38,
            ],

            // Caja Pagos: consulta tramites cuando corresponde y gestiona pagos.
            3 => [1, 5, 6, 10, 12, 30, 31],

            // Solicitante: inicia tramites propios o actua por una empresa que lo autorizo.
            4 => [1, 8, 9, 12, 19, 36],

            8 => $permisosAdministrador,
        ];

        $id = 1;

        foreach ($permisosPorRol as $role => $permisos) {
            foreach ($permisos as $permiso) {
                $this->guardar('permisos_roles', $id++, [
                    'id_permiso' => $permiso,
                    'id_role' => $role,
                ]);
            }
        }
    }
}

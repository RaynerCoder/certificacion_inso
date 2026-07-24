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
        $permisosAdministrador = DB::table('permisos')
            ->where('estado', 1)
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $permisosPorRol = [
            // El administrador recibe todos los permisos activos, incluidos los que se agreguen más adelante.
            1 => $permisosAdministrador,

            // Tecnico Evaluador: atiende trámites, consulta información técnica y ve lo que registró en ventanilla.
            2 => [
                1, 2, 3, 4, 10, 11, 12,
                18, 19, 21, 22, 23,
                24, 25, 26, 27, 28,
                29, 30, 31, 32, 33, 34, 35, 37, 38,
            ],

            // Caja Pagos: consulta tramites cuando corresponde y gestiona pagos.
            3 => [1, 5, 10, 12, 30, 31],

            // Solicitante: inicia, consulta sus tramites y, si es empresa, registra sus tramitadores.
            4 => [1, 8, 9, 12, 19, 36],

            // Representante legal: puede iniciar y seguir tramites de su empresa.
            5 => [1, 8, 9, 12, 36],

            // Tramitador: puede iniciar y seguir tramites de empresas donde este autorizado.
            6 => [1, 8, 9, 12, 36],

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

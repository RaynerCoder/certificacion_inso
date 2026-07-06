<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class PermisoRoleSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Asignacion de permisos a roles.
     */
    public function run(): void
    {
        $permisosPorRol = [
            // Administrador: acceso completo a los modulos activos del sistema.
            1 => range(1, 34),

            // Tecnico Evaluador: atiende tramites y consulta informacion tecnica necesaria.
            2 => [
                1, 2, 3, 4, 10, 11, 12,
                18, 19, 21, 22, 23,
                24, 25, 26, 27, 28,
                29, 30, 31, 32, 33, 34,
            ],

            // Caja Pagos: consulta tramites cuando corresponde y gestiona pagos.
            3 => [1, 5, 10, 12, 29, 30],

            // Solicitante: inicia y consulta sus propios tramites.
            4 => [1, 8, 9, 12],

            // Representante legal: puede iniciar y seguir tramites de su empresa.
            5 => [1, 8, 9, 12],

            // Tramitador: puede iniciar y seguir tramites de empresas donde este autorizado.
            6 => [1, 8, 9, 12],
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

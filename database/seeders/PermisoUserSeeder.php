<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class PermisoUserSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Permisos directos asignados a usuarios.
     */
    public function run(): void
    {
        foreach ([
            1 => [2, 5],
            2 => [3, 6],
        ] as $id => [$user, $permiso]) {
            $this->guardar('permisos_users', $id, [
                'id_user' => $user,
                'id_permiso' => $permiso,
            ]);
        }
    }
}

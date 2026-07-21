<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleUserSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Asignacion de roles a usuarios.
     */
    public function run(): void
    {
        // Limpia solo las cuentas de ejemplo externas para evitar roles duplicados al volver a sembrar.
        DB::table('roles_users')
            ->whereIn('id_user', [8, 9, 15, 16, 17])
            ->delete();

        foreach ([
            2 => [2, 2],
            3 => [3, 3],
            4 => [1, 4],
            5 => [2, 5],
            6 => [2, 6],
            7 => [2, 7],
            8 => [4, 8],
            9 => [4, 9],
            10 => [2, 10],
            11 => [2, 11],
            12 => [2, 12],
            13 => [2, 13],
            14 => [1, 14],
            15 => [4, 15],
            16 => [4, 16],
            17 => [4, 17],
        ] as $id => [$role, $user]) {
            $this->guardar('roles_users', $id, [
                'id_role' => $role,
                'id_user' => $user,
            ]);
        }

    }
}

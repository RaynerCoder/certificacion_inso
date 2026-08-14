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
        // Limpia las cuentas externas de prueba, incluidas las antiguas cuentas empresariales.
        DB::table('roles_users')
            ->whereIn('id_user', [8, 9, 15, 16, 17])
            ->delete();

        foreach ([
            2 => ['tecnico-evaluador', 2],
            3 => ['caja-pagos', 3],
            4 => ['administrador', 4],
            5 => ['tecnico-evaluador', 5],
            6 => ['tecnico-evaluador', 6],
            7 => ['tecnico-evaluador', 7],
            9 => ['solicitante', 9],
            10 => ['tecnico-evaluador', 10],
            11 => ['tecnico-evaluador', 11],
            12 => ['tecnico-evaluador', 12],
            13 => ['tecnico-evaluador', 13],
            14 => ['administrador', 14],
            16 => ['solicitante', 16],
            17 => ['solicitante', 17],
        ] as $id => [$slugRol, $idUsuario]) {
            $this->guardar('roles_users', $id, [
                'id_role' => $this->rolPorSlug($slugRol),
                'id_user' => $idUsuario,
            ]);
        }
    }

    private function rolPorSlug(string $slug): int
    {
        $idRol = DB::table('roles')->where('slug', $slug)->value('id');

        if (! $idRol) {
            throw new \RuntimeException("No existe el rol {$slug}. Ejecute RoleSeeder antes de RoleUserSeeder.");
        }

        return (int) $idRol;
    }
}

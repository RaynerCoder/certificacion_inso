<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdministradorSeeder extends Seeder
{
    /**
     * Registra la cuenta inicial y la vincula con el rol administrador.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $ahora = now();

            DB::table('users')->updateOrInsert(
                ['email' => 'super.admin@gmail.com'],
                [
                    'name' => 'Super Administrador',
                    'password' => Hash::make('Inso1103*'),
                    'email_verified_at' => $ahora,
                    'estado' => 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                    'deleted_at' => null,
                ]
            );

            $idUsuario = DB::table('users')
                ->where('email', 'super.admin@gmail.com')
                ->value('id');

            $idRol = DB::table('roles')
                ->where('slug', 'super-administrador')
                ->value('id');

            if (!$idUsuario || !$idRol) {
                throw new \RuntimeException('No se pudo relacionar el superadministrador con su rol.');
            }

            // La cuenta inicial usa un unico rol para que su configuracion sea clara.
            DB::table('roles_users')->where('id_user', $idUsuario)->delete();

            DB::table('roles_users')->updateOrInsert(
                [
                    'id_user' => $idUsuario,
                    'id_role' => $idRol,
                ],
                [
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]
            );
        });
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class SuperAdministradorSeeder extends Seeder
{
    /**
     * Registra la cuenta inicial con acceso completo al sistema.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            $ahora = now();

            // Este es el único rol que necesita la instalación inicial.
            DB::table('roles')->updateOrInsert(
                ['slug' => 'super-administrador'],
                [
                    'name' => 'Super Administrador',
                    'descripcion' => 'Cuenta principal con acceso total al sistema.',
                    'especial' => 'ALL',
                    'estado' => 1,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                    'deleted_at' => null,
                ]
            );

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

            // La cuenta inicial usa un único rol para mantener clara su configuración.
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

            // El rol recibe todos los permisos disponibles en la instalación.
            DB::table('permisos_roles')->where('id_role', $idRol)->delete();

            $permisos = DB::table('permisos')
                ->where('estado', 1)
                ->pluck('id');

            foreach ($permisos as $idPermiso) {
                DB::table('permisos_roles')->insert([
                    'id_permiso' => $idPermiso,
                    'id_role' => $idRol,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            }
        });
    }
}

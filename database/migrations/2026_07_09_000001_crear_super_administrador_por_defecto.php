<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    /**
     * Crea la cuenta base para poder ingresar al sistema despues de migrar.
     */
    public function up(): void
    {
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

        DB::table('roles')->updateOrInsert(
            ['slug' => 'administrador'],
            [
                'name' => 'Administrador',
                'descripcion' => 'Acceso completo para administrar el sistema.',
                'especial' => 'SISTEMA',
                'estado' => 1,
                'created_at' => $ahora,
                'updated_at' => $ahora,
                'deleted_at' => null,
            ]
        );

        $usuario = DB::table('users')->where('email', 'super.admin@gmail.com')->first();
        $rol = DB::table('roles')->where('slug', 'administrador')->first();

        if ($usuario && $rol) {
            DB::table('roles_users')->updateOrInsert(
                [
                    'id_user' => $usuario->id,
                    'id_role' => $rol->id,
                ],
                [
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]
            );
        }
    }

    /**
     * Solo retira la cuenta creada por esta migracion.
     */
    public function down(): void
    {
        $usuario = DB::table('users')->where('email', 'super.admin@gmail.com')->first();

        if ($usuario) {
            DB::table('roles_users')->where('id_user', $usuario->id)->delete();
            DB::table('users')->where('id', $usuario->id)->delete();
        }
    }
};

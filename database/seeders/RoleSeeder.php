<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Registra los roles mínimos de una instalación nueva.
     * Los roles existentes y los que se agreguen después se conservan sin cambios.
     */
    public function run(): void
    {
        $roles = [
            ['Super Administrador', 'super-administrador', 'Cuenta principal con acceso total al sistema.', 'ALL'],
            ['Funcionario', 'funcionario', 'Funcionario público que trabaja en el INSO.', 'INSO'],
            ['Administrador', 'administrador', 'Usuario responsable de gestionar la configuración, los usuarios y el funcionamiento del sistema.', 'ADMIN'],
            ['Solicitante', 'solicitante', 'Usuario autorizado para registrar y gestionar trámites propios o en representación de una empresa.', null],
            ['Tramitador', 'tramitador', 'Persona autorizada para representar a una empresa.', null],
        ];

        foreach ($roles as [$name, $slug, $descripcion, $especial]) {
            if (DB::table('roles')->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('roles')->insert([
                'name' => $name,
                'slug' => $slug,
                'descripcion' => $descripcion,
                'especial' => $especial,
                'estado' => $this->estado('roles'),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

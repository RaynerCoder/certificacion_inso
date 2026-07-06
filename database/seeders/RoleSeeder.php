<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Roles base del sistema.
     */
    public function run(): void
    {
        // Importador queda como dato del producto/persona, no como rol de acceso al sistema.
        DB::table('roles_users')->where('id_role', 7)->delete();
        DB::table('permisos_roles')->where('id_role', 7)->delete();

        if (Schema::hasColumn('roles', 'deleted_at')) {
            DB::table('roles')->where('id', 7)->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ([
            1 => ['Administrador', 'administrador', 'Administra usuarios, roles, permisos, catalogos y configuracion general.', 'SISTEMA'],
            2 => ['Tecnico Evaluador', 'tecnico-evaluador', 'Funcionario que revisa tramites, requisitos, productos, pagos y observaciones.', null],
            3 => ['Caja Pagos', 'caja-pagos', 'Funcionario encargado de registrar y validar pagos relacionados a tramites.', null],
            4 => ['Solicitante', 'solicitante', 'Persona natural o empresa que inicia y consulta tramites propios.', null],
            5 => ['Representante Legal', 'representante-legal', 'Persona que representa legalmente a una empresa ante el sistema.', null],
            6 => ['Tramitador', 'tramitador', 'Persona autorizada por una empresa para presentar y seguir tramites.', null],
        ] as $id => [$name, $slug, $descripcion, $especial]) {
            $this->guardar('roles', $id, [
                'name' => $name,
                'slug' => $slug,
                'descripcion' => $descripcion,
                'especial' => $especial,
                'estado' => $this->estado('roles'),
            ]);
        }
    }
}

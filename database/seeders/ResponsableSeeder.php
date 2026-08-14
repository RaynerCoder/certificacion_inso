<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ResponsableSeeder extends Seeder
{
    use GuardaSeeders;

    public function run(): void
    {
        // Esta relacion pertenecia al escenario anterior. Se conserva como historial,
        // pero deja de autorizar a Laura como tramitadora de BIOCONTROL.
        DB::table('responsables')
            ->where('id', 4)
            ->update([
                'fecha_baja' => '2026-07-31',
                'estado' => 'INACTIVO',
                'updated_at' => now(),
            ]);

        foreach ([
            // Mario representa legalmente a AGROPARC.
            1 => [1, 2, 'solicitante', 'documentos/responsables/agroparc-representante.pdf'],
            // Laura puede tramitar para AGROPARC, pero no es su representante legal.
            2 => [1, 4, 'tramitador', 'documentos/responsables/laura-agroparc-tramitadora.pdf'],
            // Laura representa legalmente a BIOCONTROL.
            3 => [2, 4, 'solicitante', 'documentos/responsables/biocontrol-representante.pdf'],
        ] as $id => [$empresa, $persona, $slugRol, $respaldo]) {
            $this->guardar('responsables', $id, [
                'id_empresa' => $empresa,
                'id_persona' => $persona,
                'id_rol' => $this->rolPorSlug($slugRol),
                'url_respaldo' => $respaldo,
                'fecha_registro' => '2026-01-10',
                'fecha_baja' => null,
                'estado' => 'ACTIVO',
            ]);
        }
    }

    /**
     * Busca el rol por slug para no depender de IDs fijos.
     */
    private function rolPorSlug(string $slug): int
    {
        $idRol = DB::table('roles')->where('slug', $slug)->value('id');

        if (! $idRol) {
            throw new \RuntimeException("No existe el rol {$slug}. Ejecute RoleSeeder antes de ResponsableSeeder.");
        }

        return (int) $idRol;
    }
}

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
        foreach ([
            1 => [1, 2, 'representante-legal', 'documentos/responsables/agroparc-representante.pdf'],
            2 => [1, 2, 'tramitador', 'documentos/responsables/agroparc-tramitador.pdf'],
            3 => [1, 4, 'tramitador', 'documentos/responsables/laura-agroparc-tramitadora.pdf'],
            4 => [2, 4, 'tramitador', 'documentos/responsables/laura-biocontrol-tramitadora.pdf'],
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

        if (!$idRol) {
            throw new \RuntimeException("No existe el rol {$slug}. Ejecute RoleSeeder antes de ResponsableSeeder.");
        }

        return (int) $idRol;
    }
}

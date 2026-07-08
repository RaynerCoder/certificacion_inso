<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RubroSeeder extends Seeder
{
    use GuardaSeeders;

    public function run(): void
    {
        foreach ([
            1 => ['IMPORTACION DE PLAGUICIDAS DE USO DOMESTICO', 'Importacion y gestion de productos plaguicidas.'],
            2 => ['COMERCIALIZACION DE PRODUCTOS PLAGUICIDAS', 'Venta y distribucion de productos plaguicidas.'],
            3 => ['TRAMITACION Y REPRESENTACION LEGAL', 'Gestion de tramites y representacion de empresas.'],
            4 => ['GESTION DE TRAMITES EMPRESARIALES', 'Servicios administrativos para empresas.'],
            5 => ['SOLICITUDES PERSONALES ANTE INSO', 'Solicitudes realizadas por personas naturales.'],
        ] as $id => [$nombre, $descripcion]) {
            $this->guardar('rubros', $id, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'estado' => 'ACTIVO',
            ]);
        }

        foreach ([
            1 => [1, 1],
            2 => [1, 2],
            3 => [2, 3],
            4 => [4, 4],
            5 => [5, 5],
        ] as $id => [$persona, $rubro]) {
            DB::table('personas_rubros')->updateOrInsert(
                ['id' => $id],
                [
                    'id_persona' => $persona,
                    'id_rubro' => $rubro,
                    'estado' => 'ACTIVO',
                ]
            );
        }
    }
}

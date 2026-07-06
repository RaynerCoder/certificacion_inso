<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TipoCertificadoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Tipos de certificados del sistema.
     */
    public function run(): void
    {
        $idAreaPlaguicidas = DB::table('areas')
            ->where('nombre', 'AREA DE PLAGUICIDAS')
            ->value('id');

        foreach ([
            1 => 'CERTIFICADO DE REGISTRO DE PLAGUICIDA',
            2 => 'CERTIFICADO DE IMPORTACION DE PLAGUICIDA',
        ] as $id => $nombre) {
            $this->guardar('tipos_certificados', $id, [
                'nombre' => $nombre,
                'id_area' => $idAreaPlaguicidas,
                'estado' => $this->estado('tipos_certificados'),
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class RubroSeeder extends Seeder
{
    use GuardaSeeders;

    public function run(): void
    {
        foreach ([
            1 => [1, 'IMPORTACION DE PLAGUICIDAS DE USO DOMESTICO'],
            2 => [1, 'COMERCIALIZACION DE PRODUCTOS PLAGUICIDAS'],
            3 => [2, 'TRAMITACION Y REPRESENTACION LEGAL'],
            4 => [4, 'GESTION DE TRAMITES EMPRESARIALES'],
            5 => [5, 'SOLICITUDES PERSONALES ANTE INSO'],
        ] as $id => [$persona, $nombre]) {
            $this->guardar('rubros', $id, [
                'id_persona' => $persona,
                'nombre' => $nombre,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

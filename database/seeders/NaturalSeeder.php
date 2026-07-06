<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class NaturalSeeder extends Seeder
{
    use GuardaSeeders;

    public function run(): void
    {
        foreach ([
            1 => [2, '4895621', 'LP', 'MARIO ERWIN', 'PEDRAZA', 'MERIDA', null, '1988-04-18', 1, 'REPRESENTANTE LEGAL Y TRAMITADOR'],
            2 => [4, '6154789', 'LP', 'LAURA GABRIELA', 'TORRES', 'LIMA', null, '1991-09-12', 0, 'TRAMITADORA DE EMPRESAS'],
            3 => [5, '7021458', 'LP', 'CARLOS ANDRES', 'QUISPE', 'ROJAS', null, '1993-02-04', 1, 'SOLICITANTE NATURAL'],
        ] as $id => [$persona, $ci, $expedido, $nombres, $paterno, $materno, $casado, $nacimiento, $genero, $ocupacion]) {
            $this->guardar('naturals', $id, [
                'id_persona' => $persona,
                'ci' => $ci,
                'complemento' => null,
                'expedido' => $expedido,
                'nombres' => $nombres,
                'apellido_paterno' => $paterno,
                'apellido_materno' => $materno,
                'apellido_casado' => $casado,
                'fecha_nacimiento' => $nacimiento,
                'genero' => $genero,
                'ocupacion' => $ocupacion,
            ]);
        }
    }
}

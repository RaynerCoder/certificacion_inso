<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class OcupacionCobSeeder extends Seeder
{
    use GuardaSeeders;

    public function run(): void
    {
        foreach ([
            1 => ['2', 'Profesionales cientificos e intelectuales', '21', 'Profesionales de ciencias e ingenieria', '2141', 'Ingeniero industrial'],
            2 => ['2', 'Profesionales cientificos e intelectuales', '21', 'Profesionales de ciencias e ingenieria', '2113', 'Quimico'],
            3 => ['3', 'Tecnicos y profesionales de nivel medio', '33', 'Profesionales de apoyo en administracion', '3343', 'Secretaria administrativa'],
            4 => ['4', 'Personal de apoyo administrativo', '43', 'Empleados contables y encargados del registro de materiales', '4323', 'Tramitador administrativo'],
            5 => ['1', 'Directores y gerentes', '12', 'Directores administrativos y comerciales', '1219', 'Representante legal'],
        ] as $id => [$codigoGranGrupo, $granGrupo, $codigoSubgrupo, $subgrupo, $codigoOcupacion, $ocupacion]) {
            $this->guardar('ocupaciones_cob', $id, [
                'codigo_gran_grupo' => $codigoGranGrupo,
                'descripcion_gran_grupo' => $granGrupo,
                'codigo_subgrupo_principal' => $codigoSubgrupo,
                'subgrupo_principal' => $subgrupo,
                'codigo_ocupacion' => $codigoOcupacion,
                'descripcion_ocupacion' => $ocupacion,
            ]);
        }
    }
}

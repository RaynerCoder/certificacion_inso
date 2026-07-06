<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    use GuardaSeeders;

    public function run(): void
    {
        foreach ([
            1 => [1, 4, 'AGROPARC EI S.R.L.', 'MTR-AGP-2026', '-16.500000', '-68.150000'],
            2 => [3, 4, 'BIOCONTROL BOLIVIA S.R.L.', 'MTR-BIO-2026', '-17.389500', '-66.156800'],
        ] as $id => [$persona, $tipoEmpresa, $razonSocial, $matricula, $latitud, $longitud]) {
            $this->guardar('empresas', $id, [
                'id_persona' => $persona,
                'id_tipo_empresa' => $tipoEmpresa,
                'razon_social' => $razonSocial,
                'matricula' => $matricula,
                'latitud' => $latitud,
                'longitud' => $longitud,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

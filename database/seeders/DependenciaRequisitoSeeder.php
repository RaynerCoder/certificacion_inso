<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class DependenciaRequisitoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Dependencias demo entre requisitos y certificados previos.
     */
    public function run(): void
    {
        foreach ([
            // El requisito configurado 7 exige tener el tipo de certificado 1 vigente.
            1 => ['id_requisito_tipo_certificado' => 7, 'id_tipo_certificado_requerido' => 1],
        ] as $id => $datos) {
            $this->guardar('dependencias_requisitos', $id, [
                'id_requisito_tipo_certificado' => $datos['id_requisito_tipo_certificado'],
                'id_tipo_certificado_requerido' => $datos['id_tipo_certificado_requerido'],
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

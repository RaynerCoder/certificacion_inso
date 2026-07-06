<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class RequisitoTipoCertificadoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Requisitos necesarios por tipo de certificado.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 1, 1],
            2 => [2, 1, 1],
            3 => [3, 1, 1],
            4 => [4, 1, 4],
            5 => [1, 2, 1],
            6 => [4, 2, 4],
            7 => [5, 2, 6],
        ] as $id => [$requisito, $tipo, $tipoEvidencia]) {
            $this->guardar('requisitos_tipos_certificados', $id, [
                'id_requisito' => $requisito,
                'id_tipo_certificado' => $tipo,
                'id_tipo_evidencia' => $tipoEvidencia,
                'estado' => $this->estado('requisitos_tipos_certificados'),
            ]);
        }
    }
}

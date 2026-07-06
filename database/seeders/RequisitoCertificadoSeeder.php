<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class RequisitoCertificadoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Requisitos adjuntados por el solicitante. Inician pendientes de revisión técnica.
     */
    public function run(): void
    {
        foreach ([
            1 => 1,
            2 => 2,
            3 => 3,
            4 => 4,
        ] as $id => $requisito) {
            $this->guardar('requisitos_certificados', $id, [
                'id_certificado' => 1,
                'id_requisito' => $requisito,
                'cumple' => null,
                'estado' => 'PENDIENTE_REVISION',
            ]);
        }
    }
}

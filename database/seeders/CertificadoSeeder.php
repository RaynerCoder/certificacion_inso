<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class CertificadoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Trámite demo: AGROPARC es beneficiaria y Mario actúa como representante legal.
     */
    public function run(): void
    {
        $this->guardar('certificados', 1, [
            'id_tipo_certificado' => 1,
            'id_persona_beneficiario' => 1,
            'id_persona_tramitador' => 2,
            'codigo' => 'TRM-2026-000001',
            'fecha_inicio' => '2026-06-15',
            'fecha_fin' => null,
            'descripcion' => 'Solicitud de AGROPARC EI S.R.L. presentada por su representante legal Mario Erwin Pedraza Merida.',
            'url_documento' => null,
            'estado' => 'EN_REVISION',
        ]);
    }
}

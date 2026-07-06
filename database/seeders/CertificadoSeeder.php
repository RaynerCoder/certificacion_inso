<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class CertificadoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Trámite demo: empresa beneficiaria solicita mediante persona natural tramitadora.
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
            'descripcion' => 'Solicitud de registro/autorización para productos plaguicidas presentados por AGROPARC EI S.R.L.',
            'url_documento' => null,
            'estado' => 'EN_REVISION',
        ]);
    }
}

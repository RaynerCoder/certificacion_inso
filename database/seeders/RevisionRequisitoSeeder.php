<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class RevisionRequisitoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Revision demo: permite probar historial y observaciones sin tocar la tabla principal.
     */
    public function run(): void
    {
        $this->guardar('revisiones_requisitos', 1, [
            'id_requisito_certificado' => 1,
            'id_evidencia_requisito' => 1,
            'id_usuario_revisor' => 4,
            'resultado_cumple' => 'NO',
            'estado' => 'ACTIVO',
        ]);
    }
}

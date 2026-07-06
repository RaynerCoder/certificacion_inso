<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class ObservacionRequisitoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Observacion demo ligada a una revision tecnica exacta.
     */
    public function run(): void
    {
        $this->guardar('observaciones_requisitos', 1, [
            'id_revision_requisito' => 1,
            'observacion' => 'El documento PDF debe corresponder al tramite solicitado.',
            'estado' => 'ACTIVA',
        ]);
    }
}

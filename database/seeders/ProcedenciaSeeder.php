<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class ProcedenciaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Procedencias administrativas de pagos.
     */
    public function run(): void
    {
        foreach ([
            1 => ['PROC-CERT-REG', 'Pago por certificado de registro'],
            2 => ['PROC-CERT-IMP', 'Pago por certificado de importacion'],
        ] as $id => [$codigo, $descripcion]) {
            $this->guardar('procedencias', $id, [
                'codigo' => $codigo,
                'descripcion' => $descripcion,
            ]);
        }
    }
}

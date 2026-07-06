<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class CertificadoRegistroSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * El mismo trámite puede incluir varios registros y productos.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 1],
            2 => [1, 2],
            3 => [1, 3],
        ] as $id => [$certificado, $registro]) {
            $this->guardar('certificados_registros', $id, [
                'id_certificado' => $certificado,
                'id_registro' => $registro,
            ]);
        }
    }
}

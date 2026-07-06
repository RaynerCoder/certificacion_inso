<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class PagoCertificadoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Vincula el pago inicial con el trámite demo.
     */
    public function run(): void
    {
        $this->guardar('pagos_certificados', 1, [
            'id_certificado' => 1,
            'id_pago' => 1,
        ]);
    }
}

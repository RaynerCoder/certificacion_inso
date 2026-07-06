<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class PagoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Pago inicial del trámite demo. Queda pendiente de validación.
     */
    public function run(): void
    {
        $this->guardar('pagos', 1, [
            'id_procedencia' => 1,
            'tipo_pago' => 'TRANSFERENCIA',
            'fecha' => '2026-06-15',
            'comprobante' => 'storage/documentos/pagos/tramites/1/comprobante.pdf',
            'monto' => 350.00,
            'id_cliente' => 1,
            'id_funcionario' => null,
            'fecha_validacion' => null,
            'factura' => null,
        ]);
    }
}

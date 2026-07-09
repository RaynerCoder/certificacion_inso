<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class RegistroSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Registros usados en el trámite demo.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 'INSO-RP-2026-0001', '2029-05-17', 4320, 1, 1],
            2 => [1, 'INSO-RP-2026-0002', '2029-05-17', 720, 9, 2],
            3 => [2, 'INSO-RP-2026-0003', '2029-05-17', 8400, 9, 3],
        ] as $id => [$producto, $codigo, $vigencia, $cantidad, $unidad, $presentacion]) {
            $this->guardar('registros', $id, [
                'id_producto' => $producto,
                'codigo_autorizacion' => $codigo,
                'fecha_vigencia' => $vigencia,
                'cantidad' => $cantidad,
                'id_catalogo_unidad' => $unidad,
                'id_presentacion' => $presentacion,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

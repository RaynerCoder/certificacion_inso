<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class ProductoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Productos del trámite demo. Ambos pertenecen a la misma empresa importadora.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 'PROD-SPM-001', 1, 1, 'SPIROMAT', 'Pralletrina + Permetrina', 'INSECTICIDA AEROSOL', 1],
            2 => [1, 'PROD-SAP-002', 1, 2, 'SAPOLIO INSECTICIDA MATA TODO', 'Imidacloprid', 'INSECTICIDA DOMESTICO', 1],
        ] as $id => [$importador, $codigo, $territorio, $fabricante, $comercial, $cientifico, $clasificacion, $tipo]) {
            $this->guardar('productos', $id, [
                'id_importador_persona' => $importador,
                'codigo' => $codigo,
                'id_territorio_pais' => $territorio,
                'id_fabricante' => $fabricante,
                'nombre_comercial' => $comercial,
                'nombre_cientifico' => $cientifico,
                'clasificacion' => $clasificacion,
                'id_tipo_producto' => $tipo,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

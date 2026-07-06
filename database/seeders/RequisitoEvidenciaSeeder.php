<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class RequisitoEvidenciaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Evidencias demo para los requisitos del tramite inicial.
     */
    public function run(): void
    {
        foreach ([
            1 => ['id_requisito_certificado' => 1, 'id_tipo_evidencia' => 1, 'valor' => 'storage/documentos/requisitos_certificados/1/documento.pdf'],
            2 => ['id_requisito_certificado' => 2, 'id_tipo_evidencia' => 1, 'valor' => 'storage/documentos/requisitos_certificados/2/documento.pdf'],
            3 => ['id_requisito_certificado' => 3, 'id_tipo_evidencia' => 1, 'valor' => 'storage/documentos/requisitos_certificados/3/documento.pdf'],
            4 => ['id_requisito_certificado' => 4, 'id_tipo_evidencia' => 4, 'valor' => '1'],
        ] as $id => $datos) {
            $this->guardar('evidencias_requisitos', $id, [
                'id_requisito_certificado' => $datos['id_requisito_certificado'],
                'id_tipo_evidencia' => $datos['id_tipo_evidencia'],
                'valor' => $datos['valor'],
                'estado' => 'REGISTRADO',
            ]);
        }
    }
}

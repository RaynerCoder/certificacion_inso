<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class TipoEvidenciaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Formas permitidas para cumplir un requisito del tramite.
     */
    public function run(): void
    {
        foreach ([
            1 => ['PDF', 'Documento PDF', 'Archivo PDF presentado por el solicitante o cargado por funcionario.', 10],
            2 => ['IMAGEN', 'Imagen', 'Archivo de imagen presentado como respaldo.', 5],
            3 => ['TEXTO', 'Texto', 'Dato escrito o declaracion capturada en el sistema.', 0],
            4 => ['PAGO', 'Pago validado', 'Referencia al pago registrado en la tabla pagos.', 0],
            5 => ['PRESENCIAL', 'Validacion presencial', 'Constancia registrada por funcionario despues de revisar fisicamente.', 0],
            6 => ['CERTIFICADO', 'Certificado', 'Referencia a un certificado previo requerido por el tramite.', 0],
            7 => ['PRODUCTO', 'Producto', 'Referencia a un producto registrado en el sistema.', 0],
            8 => ['TRAMITADOR', 'Tramitador', 'Validación de la persona autorizada para realizar trámites de una empresa.', 0],
        ] as $id => [$codigo, $nombre, $descripcion, $tamanioMaximoMb]) {
            $this->guardar('tipos_evidencias', $id, [
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'tamanio_maximo_mb' => $tamanioMaximoMb,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

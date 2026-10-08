<?php

namespace App\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class OpenBcbMockService
{

    // Simula la respuesta de POST /v1/qr utilizando una respuesta real guardada.
    public function generarQr(array $datos): array
    {
        $rutaRespuesta = 'openbcb/mock/respuesta_generar_qr.json';

        // La prueba necesita el JSON que contiene la imagen Base64.
        if (!Storage::disk('local')->exists($rutaRespuesta)) {
            throw new \RuntimeException(
                'No se encontró la respuesta simulada para generar el QR.'
            );
        }

        // Lee la respuesta capturada de OpenBCB.
        $respuesta = json_decode(
            Storage::disk('local')->get($rutaRespuesta),
            true,
            512,
            JSON_THROW_ON_ERROR
        );

        // Genera otro identificador para evitar repetir el idQr capturado.
        $respuesta['datos']['idQr'] = 'QR-SIM-' . Str::uuid();

        // Aclara que la respuesta corresponde al simulador.
        $respuesta['mensaje'] = 'QR simulado generado correctamente.';

        return $respuesta;
    }




    // Simula la respuesta de GET /v1/qr/{idQR}.
    public function consultarQr(string $idQr): array
    {
        return [
            'finalizado' => true,
            'mensaje' => 'Operación simulada correctamente',
            'datos' => [
                // OpenBCB devuelve el identificador del QR consultado.
                'idQr' => $idQr,

                // Identificador de entidad utilizado solamente en la respuesta simulada.
                'idEntidad' => 1000,

                // Información adicional devuelta por OpenBCB.
                'metaData' => [
                    'modo' => 'simulacion',
                ],

                // OpenBCB devuelve las operaciones encontradas dentro de ordenes.
                'ordenes' => [
                    [
                        'idQr' => $idQr,
                        // La misma operación conserva su identificador al volver a consultar el QR.
                        'idOrdenDestinatario' => 'ORD-SIM-' . strtoupper(substr(hash('sha256', $idQr), 0, 20)),
                        'codMoneda' => 'BOB',
                        'importe' => 100.25,
                        'fecha' => now()->format('Y-m-d\TH:i:s'),
                        'ciNitOriginante' => '12345678',
                        'nombreOriginante' => 'Pagador de prueba',
                        'cuentaOrigen' => '233333444',
                        'eifOrigen' => 'MLD1014',
                        'estado' => 'PROCESADO',
                    ],
                ],
            ],
        ];
    }    
}
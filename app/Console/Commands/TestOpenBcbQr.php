<?php

namespace App\Console\Commands;

use App\Services\OpenBcbService;
use Illuminate\Console\Command;
use Throwable;

class TestOpenBcbQr extends Command
{
    /**
     * Comando para preparar o generar QR de prueba.
     *
     * Modo simulación, NO envía nada:
     * php artisan bcb:test-qr 10 --dry-run
     *
     * Modo real, sí enviaría a OpenBCB:
     * php artisan bcb:test-qr 10
     */
    protected $signature = 'bcb:test-qr 
        {importe=10 : Monto de prueba para generar el QR}
        {--dry-run : Solo muestra el JSON, no envía la petición a OpenBCB}';

    protected $description = 'Prepara o genera un QR de prueba en OpenBCB';

    public function handle(OpenBcbService $openBcbService): int
    {
        $jsonOptions = JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES;

        try {
            /*
             * Monto recibido desde consola.
             * Ejemplo:
             * php artisan bcb:test-qr 10 --dry-run
             */
            $importe = (float) $this->argument('importe');

            /*
             * Armamos el cuerpo JSON que OpenBCB pide para generar QR.
             *
             * Según Swagger, POST /v1/qr necesita:
             * titularDestinatario, ciNitDestinatario, eif, cuentaDestino,
             * codMoneda, importe, glosa, fechaVencimiento, unicoUso,
             * codigoServicio y metaData.
             */
            $datos = [
                'titularDestinatario' => config('services.openbcb.qr.titular_destinatario'),
                'ciNitDestinatario' => config('services.openbcb.qr.ci_nit_destinatario'),
                'eif' => config('services.openbcb.qr.eif'),
                'cuentaDestino' => config('services.openbcb.qr.cuenta_destino'),

                /*
                 * Si no vas a dividir el pago entre varias cuentas,
                 * se manda como objeto vacío.
                 */
                //'cuentaDestinoDistribucion' => new \stdClass(),
                'cuentaDestinoDistribucion' => [
                    config('services.openbcb.qr.cuenta_destino') => $importe,
                ],

                'codMoneda' => config('services.openbcb.qr.cod_moneda', 'BOB'),
                'importe' => $importe,
                'glosa' => 'Pago de prueba Certificador INSO',
                'fechaVencimiento' => now()->addDay()->format('Y-m-d H:i:s'),
                'unicoUso' => true,
                'codigoServicio' => config('services.openbcb.qr.codigo_servicio', '0'),

                /*
                 * metaData sirve para relacionar el QR con tu sistema.
                 * Después aquí puedes enviar tramite_id, certificado_id,
                 * codigo_pago o usuario_id.
                 */
                'metaData' => [
                    'sistema' => 'Certificador INSO',
                    'ambiente' => 'pruebas',
                    'referencia' => 'TEST-' . now()->format('YmdHis'),
                ],
            ];

            /*
             * MODO SIMULACIÓN:
             * Esto NO llama a OpenBCB.
             * Solo muestra qué JSON se enviaría.
             */
            if ($this->option('dry-run')) {
                $this->warn('MODO SIMULACIÓN: no se enviará nada a OpenBCB.');

                $this->newLine();
                $this->info('JSON que se enviaría a POST /v1/qr:');
                $this->line(json_encode($datos, $jsonOptions));

                return self::SUCCESS;
            }

            /*
             * MODO REAL:
             * Esta parte sí enviaría la petición a OpenBCB.
             * Todavía NO la ejecutes hasta revisar primero el dry-run.
             */
            $this->info('Generando QR de prueba en OpenBCB...');

            $respuesta = $openBcbService->generarQr($datos);

            $this->newLine();
            $this->info('Respuesta de OpenBCB:');
            $this->line(json_encode($respuesta, $jsonOptions));

            $idQr = data_get($respuesta, 'datos.idQr');

            if ($idQr) {
                $this->newLine();
                $this->info('ID QR generado: ' . $idQr);
                $this->warn('Guarda este ID QR para consultar el estado después.');
            }

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->newLine();
            $this->error('No se pudo preparar/generar el QR.');
            $this->line($e->getMessage());

            $this->newLine();
            $this->warn('Revise cuenta destino, monto, firma, endpoints, credenciales y permisos.');

            return self::FAILURE;
        }
    }
}
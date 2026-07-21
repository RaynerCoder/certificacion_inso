<?php

namespace App\Console\Commands;

use App\Services\OpenBcbService;
use Illuminate\Console\Command;
use Throwable;

class TestOpenBcb extends Command
{
    /**
     * Comando para probar OpenBCB.
     *
     * Uso 1:
     * php artisan bcb:test-conexion
     *
     * Uso 2:
     * php artisan bcb:test-conexion --entidad
     */
    protected $signature = 'bcb:test-conexion {--entidad : También consulta la entidad con firma OpenBCB}';

    /**
     * Descripción del comando.
     */
    protected $description = 'Prueba la conexión local con OpenBCB ambiente de pruebas';

    /**
     * Ejecuta la prueba.
     */
    public function handle(OpenBcbService $openBcbService): int
    {
        $jsonOptions = JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES;

        try {
            /*
             * PRUEBA 1:
             * Siempre se prueba primero la conexión base.
             *
             * Esto llama a:
             * https://openbcb.pruebas.bcb.gob.bo/api
             */
            $this->info('Probando conexión base con OpenBCB pruebas...');

            $base = $openBcbService->probarServicioBase();

            $this->newLine();
            $this->info('Conexión base correcta.');
            $this->line('Respuesta base:');
            $this->line(json_encode($base, $jsonOptions));

            /*
             * PRUEBA 2:
             * Solo se ejecuta si usas:
             * php artisan bcb:test-conexion --entidad
             *
             * Esto llama a:
             * GET /v1/entidades/{idEntidad}
             *
             * Esta prueba ya requiere firma OpenBCB.
             */
            if ($this->option('entidad')) {
                $this->newLine();
                $this->info('Probando consulta autenticada de entidad...');

                $entidad = $openBcbService->obtenerEntidad();

                $this->newLine();
                $this->info('Respuesta de entidad recibida:');
                $this->line(json_encode($entidad, $jsonOptions));
            }

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->newLine();
            $this->error('No se pudo completar la prueba con OpenBCB.');
            $this->line($e->getMessage());

            $this->newLine();
            $this->warn('Revise: .env, endpoints, Entity ID, Key ID, Access Token, Shared Secret, firma, VPN/IP o permisos.');

            return self::FAILURE;
        }
    }
}
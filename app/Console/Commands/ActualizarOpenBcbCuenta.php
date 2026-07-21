<?php

namespace App\Console\Commands;

use App\Services\OpenBcbService;
use Illuminate\Console\Command;
use Throwable;

class ActualizarOpenBcbCuenta extends Command
{
    /**
     * Simulación:
     * php artisan bcb:actualizar-cuenta --dry-run
     *
     * Actualización real:
     * php artisan bcb:actualizar-cuenta --confirmar
     */
    protected $signature = 'bcb:actualizar-cuenta
        {--dry-run : Solo muestra el JSON, no envía nada a OpenBCB}
        {--confirmar : Confirma que se actualizará la cuenta en OpenBCB}';

    protected $description = 'Actualiza una cuenta existente en OpenBCB';

    public function handle(OpenBcbService $openBcbService): int
    {
        $jsonOptions = JSON_PRETTY_PRINT
            | JSON_UNESCAPED_UNICODE
            | JSON_UNESCAPED_SLASHES;

        try {
            $cta = env('OPENBCB_CUENTA_ACTUALIZAR_CTA');

            $datos = [
                'eif' => env('OPENBCB_CUENTA_ACTUALIZAR_EIF'),
                'eifCuenta' => env('OPENBCB_CUENTA_ACTUALIZAR_EIF_CUENTA'),
                'ciNitTitular' => env('OPENBCB_CUENTA_ACTUALIZAR_CI_NIT_TITULAR'),
                'nombreTitular' => env('OPENBCB_CUENTA_ACTUALIZAR_NOMBRE_TITULAR'),
                'estado' => env('OPENBCB_CUENTA_ACTUALIZAR_ESTADO', 'ACTIVO'),
            ];

            if (!$cta) {
                $this->error('Falta OPENBCB_CUENTA_ACTUALIZAR_CTA en el .env.');
                return self::FAILURE;
            }

            foreach ($datos as $campo => $valor) {
                if (blank($valor)) {
                    $this->error("Falta configurar: {$campo}");
                    $this->warn('Revise las variables OPENBCB_CUENTA_ACTUALIZAR_* en el .env.');
                    return self::FAILURE;
                }
            }

            if ($this->option('dry-run')) {
                $this->warn('MODO SIMULACIÓN: no se enviará nada a OpenBCB.');

                $this->newLine();
                $this->info('CTA que se actualizaría:');
                $this->line($cta);

                $this->newLine();
                $this->info('JSON que se enviaría a PUT /v1/cuentas/{cta}:');
                $this->line(json_encode($datos, $jsonOptions));

                return self::SUCCESS;
            }

            if (!$this->option('confirmar')) {
                $this->warn('Seguridad: no se envió nada a OpenBCB.');
                $this->warn('Primero revise con: php artisan bcb:actualizar-cuenta --dry-run');
                $this->warn('Para actualizar realmente use: php artisan bcb:actualizar-cuenta --confirmar');

                return self::SUCCESS;
            }

            $this->info('Actualizando cuenta en OpenBCB...');

            $respuesta = $openBcbService->actualizarCuenta($cta, $datos);

            $this->newLine();
            $this->info('Respuesta de OpenBCB:');
            $this->line(json_encode($respuesta, $jsonOptions));

            $this->newLine();
            $this->warn('Ahora verifique con: php artisan bcb:test-conexion --entidad');

            return self::SUCCESS;

        } catch (Throwable $e) {
            $this->newLine();
            $this->error('No se pudo actualizar la cuenta en OpenBCB.');
            $this->line($e->getMessage());

            return self::FAILURE;
        }
    }
}
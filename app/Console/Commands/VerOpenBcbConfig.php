<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Comando para verificar si Laravel está leyendo las variables OpenBCB.
 *
 * No imprime credenciales reales.
 * Solo muestra si cada variable está CONFIGURADA o NO CONFIGURADA.
 */
class VerOpenBcbConfig extends Command
{
    protected $signature = 'bcb:ver-config';

    protected $description = 'Verifica que Laravel esté leyendo la configuración OpenBCB';

    public function handle(): int
    {
        $this->info('Verificando configuración OpenBCB...');

        $baseUrl = config('services.openbcb.base_url');
        $entityId = config('services.openbcb.entity_id');
        $keyId = config('services.openbcb.key_id');
        $sharedSecret = config('services.openbcb.shared_secret');
        $accessToken = config('services.openbcb.access_token');

        $this->line('BASE URL: ' . ($baseUrl ?: 'NO CONFIGURADO'));
        $this->line('ENTITY ID: ' . ($entityId ? 'CONFIGURADO' : 'NO CONFIGURADO'));
        $this->line('KEY ID: ' . ($keyId ? 'CONFIGURADO' : 'NO CONFIGURADO'));
        $this->line('SHARED SECRET: ' . ($sharedSecret ? 'CONFIGURADO' : 'NO CONFIGURADO'));
        $this->line('ACCESS TOKEN: ' . ($accessToken ? 'CONFIGURADO' : 'NO CONFIGURADO'));

        return self::SUCCESS;
    }
}

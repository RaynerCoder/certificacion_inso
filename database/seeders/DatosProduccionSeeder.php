<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatosProduccionSeeder extends Seeder
{
    /**
     * Carga solamente la configuración necesaria para iniciar el sistema.
     */
    public function run(): void
    {
        $this->call([
            PermisoSeeder::class,
            SuperAdministradorSeeder::class,
        ]);
    }
}

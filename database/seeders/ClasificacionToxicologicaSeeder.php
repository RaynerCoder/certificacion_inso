<?php

namespace Database\Seeders;

use App\Models\ClasificacionToxicologica;
use Illuminate\Database\Seeder;

class ClasificacionToxicologicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clasificacionesToxicologicas = [
            [
                'descripcion' => 'INSECTICIDA',
                'codigo' => 'TP-INSE',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'HERBICIDA',
                'codigo' => 'TP-HERB',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'FUNGICIDA',
                'codigo' => 'TP-FUNG',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'ACARICIDA',
                'codigo' => 'TP-ACAR',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'NEMATICIDA',
                'codigo' => 'TP-NEMA',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'RODENTICIDA',
                'codigo' => 'TP-RODE',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'BACTERICIDA',
                'codigo' => 'TP-BACT',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'REGULADOR DE CRECIMIENTO',
                'codigo' => 'TP-REGC',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'COADYUVANTE',
                'codigo' => 'TP-COAD',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'FERTILIZANTE',
                'codigo' => 'TP-FERT',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'BIOPLAGUICIDA',
                'codigo' => 'TP-BIO',
                'estado' => 'ACTIVO',
            ],
            [
                'descripcion' => 'OTROS',
                'codigo' => 'TP-OTR',
                'estado' => 'INACTIVO',
            ],

        ];

        foreach ($clasificacionesToxicologicas as $elemento) {
            // El codigo es unico; se actualiza si ya existe.
            ClasificacionToxicologica::updateOrCreate(
                ['codigo' => $elemento['codigo']],
                $elemento
            );
        }
    }
}

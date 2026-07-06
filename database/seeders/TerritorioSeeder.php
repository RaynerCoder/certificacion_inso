<?php

namespace Database\Seeders;

use App\Models\Territorio;
use Illuminate\Database\Seeder;

class TerritorioSeeder extends Seeder
{
    /**
     * Carga territorios base sin duplicar codigos existentes.
     */
    public function run(): void
    {
        $territorios = [
            ['id_ambito' => 1, 'codigo_padre' => null, 'nombre' => 'BOLIVIA', 'codigo' => 'BO', 'estado' => 'ACTIVO'],

            ['id_ambito' => 2, 'codigo_padre' => 'BO', 'nombre' => 'LA PAZ', 'codigo' => 'BO-LP', 'estado' => 'ACTIVO'],
            ['id_ambito' => 2, 'codigo_padre' => 'BO', 'nombre' => 'COCHABAMBA', 'codigo' => 'BO-CB', 'estado' => 'ACTIVO'],
            ['id_ambito' => 2, 'codigo_padre' => 'BO', 'nombre' => 'SANTA CRUZ', 'codigo' => 'BO-SC', 'estado' => 'ACTIVO'],

            ['id_ambito' => 3, 'codigo_padre' => 'BO-LP', 'nombre' => 'MURILLO', 'codigo' => 'BO-LP-MUR', 'estado' => 'ACTIVO'],
            ['id_ambito' => 3, 'codigo_padre' => 'BO-LP', 'nombre' => 'LOS ANDES', 'codigo' => 'BO-LP-LA', 'estado' => 'ACTIVO'],

            ['id_ambito' => 3, 'codigo_padre' => 'BO-CB', 'nombre' => 'QUILLACOLLO', 'codigo' => 'BO-CB-QUI', 'estado' => 'ACTIVO'],
            ['id_ambito' => 3, 'codigo_padre' => 'BO-CB', 'nombre' => 'CHAPARE', 'codigo' => 'BO-CB-CHA', 'estado' => 'ACTIVO'],

            ['id_ambito' => 3, 'codigo_padre' => 'BO-SC', 'nombre' => 'ANDRES IBANEZ', 'codigo' => 'BO-SC-AI', 'estado' => 'ACTIVO'],
            ['id_ambito' => 3, 'codigo_padre' => 'BO-SC', 'nombre' => 'WARNES', 'codigo' => 'BO-SC-WAR', 'estado' => 'INACTIVO'],
        ];

        foreach ($territorios as $elemento) {
            $codigoPadre = $elemento['codigo_padre'];
            unset($elemento['codigo_padre']);

            // Resuelve el padre por codigo para no depender de IDs fijos.
            $elemento['id_padre_territorio'] = $codigoPadre
                ? Territorio::where('codigo', $codigoPadre)->value('id')
                : null;

            // El codigo es unico; si ya existe, se actualiza en vez de duplicarse.
            Territorio::updateOrCreate(
                ['codigo' => $elemento['codigo']],
                $elemento
            );
        }
    }
}

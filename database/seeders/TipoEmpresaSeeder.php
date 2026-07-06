<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class TipoEmpresaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tiposEmpresas = [
            // TIPO EMPRESA 1
            [
                'id' => 1,
                'descripcion' => 'SOCIEDAD DE RESPONSABILIDAD LIMITADA',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 2
            [
                'id' => 2,
                'descripcion' => 'SOCIEDAD ANONIMA',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 3
            [
                'id' => 3,
                'descripcion' => 'EMPRESA UNIPERSONAL',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 4
            [
                'id' => 4,
                'descripcion' => 'IMPORTADORA',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 5
            [
                'id' => 5,
                'descripcion' => 'DISTRIBUIDORA DE AGROQUIMICOS',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 6
            [
                'id' => 6,
                'descripcion' => 'COMERCIALIZADORA DE PLAGUICIDAS',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 7
            [
                'id' => 7,
                'descripcion' => 'FORMULADORA DE PRODUCTOS FITOSANITARIOS',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 8
            [
                'id' => 8,
                'descripcion' => 'LABORATORIO AGROQUIMICO',
                'estado' => 'ACTIVO',
            ],

            // TIPO EMPRESA 9
            [
                'id' => 9,
                'descripcion' => 'EMPRESA DE CONTROL FITOSANITARIO',
                'estado' => 'INACTIVO',
            ],

        ];

        foreach ($tiposEmpresas as $elemento) {
            $id = $elemento['id'];
            unset($elemento['id']);

            $this->guardar('tipos_empresas', $id, $elemento);
        }
    }
}

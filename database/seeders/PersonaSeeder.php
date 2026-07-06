<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Personas demo del flujo:
     * - ID 1: empresa beneficiaria/importadora.
     * - ID 2: persona natural tramitadora y representante.
     */
    public function run(): void
    {
        foreach ([
            1 => [
                'id_usuario' => 8,
                'domicilio' => 'Av. Busch, Edificio Agrocentro, La Paz',
                'nit' => '1028753027',
                'correo' => 'tramites@agroparc.test',
                'id_territorio' => $this->territorioPorCodigo('BO-LP'),
                'estado' => 'ACTIVO',
            ],
            2 => [
                'id_usuario' => 9,
                'domicilio' => 'Zona Sopocachi, calle Ecuador 123',
                'nit' => '4895621',
                'correo' => 'mario.pedraza@agroparc.test',
                'id_territorio' => $this->territorioPorCodigo('BO-LP'),
                'estado' => 'ACTIVO',
            ],
            3 => [
                'id_usuario' => 15,
                'domicilio' => 'Av. America 455, Cochabamba',
                'nit' => '3045567012',
                'correo' => 'tramites@biocontrol.test',
                'id_territorio' => $this->territorioPorCodigo('BO-CB'),
                'estado' => 'ACTIVO',
            ],
            4 => [
                'id_usuario' => 16,
                'domicilio' => 'Zona Miraflores, calle Villalobos 220',
                'nit' => null,
                'correo' => 'laura.torres@tramitadores.test',
                'id_territorio' => $this->territorioPorCodigo('BO-LP'),
                'estado' => 'ACTIVO',
            ],
            5 => [
                'id_usuario' => 17,
                'domicilio' => 'Zona Central, calle Ingavi 85',
                'nit' => null,
                'correo' => 'carlos.quispe@personal.test',
                'id_territorio' => $this->territorioPorCodigo('BO-LP'),
                'estado' => 'ACTIVO',
            ],
        ] as $id => $datos) {
            $this->guardar('personas', $id, $datos);
        }
    }

    /**
     * Busca el territorio por codigo para mantener los seeders legibles.
     */
    private function territorioPorCodigo(string $codigo): int
    {
        return (int) (DB::table('territorios')->where('codigo', $codigo)->value('id') ?: 1);
    }
}

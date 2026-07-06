<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class FuncionarioCargoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Asignaciones iniciales de cargos a funcionarios.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 1],
            2 => [2, 2],
            3 => [3, 3],
            4 => [4, 4],
            5 => [5, 5],
            6 => [6, 6],
            7 => [7, 7],
            8 => [8, 8],
            9 => [9, 9],
            10 => [10, 10],
            11 => [11, 11],
            12 => [12, 12],
        ] as $id => [$idFuncionario, $idCargo]) {
            $this->guardar('funcionarios_cargos', $id, [
                'id_funcionario' => $idFuncionario,
                'id_cargo' => $idCargo,
            ]);
        }
    }
}

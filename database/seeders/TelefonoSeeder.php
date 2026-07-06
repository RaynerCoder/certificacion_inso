<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class TelefonoSeeder extends Seeder
{
    use GuardaSeeders;

    public function run(): void
    {
        foreach ([
            1 => [1, '22147852'],
            2 => [1, '72001001'],
            3 => [2, '72001002'],
            4 => [3, '44550011'],
            5 => [4, '71234567'],
            6 => [5, '78965412'],
        ] as $id => [$persona, $numero]) {
            $this->guardar('telefonos', $id, [
                'id_persona' => $persona,
                'numero' => $numero,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

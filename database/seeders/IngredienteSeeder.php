<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class IngredienteSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Ingredientes activos de los productos demo.
     */
    public function run(): void
    {
        foreach ([
            1 => ['Pralletrina', 'Pralletrina 0.10%', 'Irritante, evitar inhalación directa'],
            2 => ['Permetrina', 'Permetrina 0.20%', 'Tóxico para organismos acuáticos'],
            3 => ['Imidacloprid', 'Imidacloprid 0.35%', 'Riesgo para abejas y organismos acuáticos'],
        ] as $id => [$nombre, $composicion, $riesgo]) {
            $this->guardar('ingredientes', $id, [
                'nombre' => $nombre,
                'composicion' => $composicion,
                'riesgo_salud' => $riesgo,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

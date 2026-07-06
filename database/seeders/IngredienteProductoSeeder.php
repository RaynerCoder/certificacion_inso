<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class IngredienteProductoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Porcentajes de ingredientes por producto demo.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 1, 0.10],
            2 => [1, 2, 0.20],
            3 => [2, 3, 0.35],
        ] as $id => [$producto, $ingrediente, $porcentaje]) {
            $this->guardar('ingredientes_productos', $id, [
                'id_producto' => $producto,
                'id_ingrediente' => $ingrediente,
                'porcentaje' => $porcentaje,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

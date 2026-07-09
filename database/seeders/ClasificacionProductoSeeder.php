<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class ClasificacionProductoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Clasificaciones usadas para registrar productos.
     */
    public function run(): void
    {
        foreach ([
            1 => ['INSECTICIDA', 'Producto destinado al control de insectos.'],
            2 => ['RODENTICIDA', 'Producto destinado al control de roedores.'],
            3 => ['DESINFECTANTE', 'Producto destinado a desinfección de superficies o ambientes.'],
            4 => ['REPELENTE', 'Producto destinado a repeler organismos no deseados.'],
        ] as $id => [$nombre, $descripcion]) {
            $this->guardar('clasificaciones_productos', $id, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

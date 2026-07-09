<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class CatalogoMedidaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Catálogo base para unidades y presentaciones comerciales.
     */
    public function run(): void
    {
        foreach ([
            1 => ['LITRO', 'L', 'unidad de medida'],
            2 => ['MILILITRO', 'ml', 'unidad de medida'],
            3 => ['KILOGRAMO', 'kg', 'unidad de medida'],
            4 => ['GRAMO', 'g', 'unidad de medida'],
            5 => ['UNIDAD', 'unid.', 'unidad de medida'],
            6 => ['FRASCO', 'frasco', 'presentacion'],
            7 => ['FRASCOS', 'frascos', 'presentacion'],
            8 => ['CAJA', 'caja', 'presentacion'],
            9 => ['CAJAS', 'cajas', 'presentacion'],
            10 => ['BOTELLA', 'botella', 'presentacion'],
        ] as $id => [$nombre, $abreviatura, $tipo]) {
            $this->guardar('catalogos_medidas', $id, [
                'nombre' => $nombre,
                'abreviatura' => $abreviatura,
                'tipo' => $tipo,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

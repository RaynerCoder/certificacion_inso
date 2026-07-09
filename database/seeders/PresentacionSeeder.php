<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class PresentacionSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Presentaciones comerciales de los productos demo.
     */
    public function run(): void
    {
        foreach ([
            1 => [1, 'etiquetas/spiromat-360ml.pdf', 1, 6, '1 x 360 ml frasco aerosol'],
            2 => [1, 'etiquetas/spiromat-spray.pdf', 12, 7, 'Caja con 12 frascos spray de 160, 230, 250, 360, 396, 400, 414, 432 y 460 ml'],
            3 => [2, 'etiquetas/sapolio-500ml.pdf', 1, 6, '1 x 500 ml gatillo atomizador'],
        ] as $id => [$producto, $etiqueta, $cantidad, $unidad, $descripcion]) {
            $this->guardar('presentaciones', $id, [
                'id_producto' => $producto,
                'url_etiqueta' => $etiqueta,
                'cantidad' => $cantidad,
                'id_catalogo_unidad' => $unidad,
                'descripcion' => $descripcion,
                'estado' => 'ACTIVO',
            ]);
        }
    }
}

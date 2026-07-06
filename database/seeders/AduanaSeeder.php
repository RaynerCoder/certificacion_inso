<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class AduanaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Datos aduaneros conectados a productos.
     */
    public function run(): void
    {
        $this->guardar('aduanas', 1, [
            'codigo_cotizacion' => 'COT-ADU-2026-001',
            'index_solicitud' => 'IDX-2026-001',
            'codigo_solicitud' => 'SOL-IMP-2026-001',
            'nombre_operativo' => 'Importacion de herbicida GLIFOSATO 48 SL',
            'acta_int' => 'ACTA-001-2026',
            'item' => '1',
            'caracteristica' => 'Producto formulado para control de malezas',
            'marca' => 'GLIFOSATO 48 SL',
            'vencimiento' => '2028-12-31',
            'unidad' => 'LITRO',
            'medida' => 'Bidon',
            'peso' => '2000',
            'observacion' => 'Lote sujeto a verificacion documental',
            'id_producto' => 1,
            'estado' => $this->estado('aduanas'),
        ]);
    }
}

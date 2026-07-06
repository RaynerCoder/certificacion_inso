<?php

namespace Database\Seeders;

use App\Models\Fabricante;
use Illuminate\Database\Seeder;

class FabricanteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $fabricantes = [
            [
                'nombre' => 'SYNGENTA',
                'razon_social' => 'SYNGENTA BOLIVIA S.R.L.',
                'descripcion' => 'Empresa multinacional dedicada a la producción de agroquímicos y semillas.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'BAYER',
                'razon_social' => 'BAYER S.A. BOLIVIA',
                'descripcion' => 'Empresa global en ciencias de la vida, incluyendo protección de cultivos.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'BASF',
                'razon_social' => 'BASF BOLIVIA S.A.',
                'descripcion' => 'Industria química con soluciones para la agricultura.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'FMC',
                'razon_social' => 'FMC BOLIVIA S.R.L.',
                'descripcion' => 'Empresa especializada en soluciones agrícolas y plaguicidas.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'UPL',
                'razon_social' => 'UPL BOLIVIA S.R.L.',
                'descripcion' => 'Proveedor global de soluciones agrícolas sostenibles.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'ADAMA',
                'razon_social' => 'ADAMA BOLIVIA',
                'descripcion' => 'Empresa enfocada en protección de cultivos.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'INTEROC',
                'razon_social' => 'INTEROC S.A. BOLIVIA',
                'descripcion' => 'Empresa de distribución de insumos agrícolas.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'QUIMBOL',
                'razon_social' => 'QUIMBOL S.R.L.',
                'descripcion' => 'Empresa local de productos químicos agrícolas.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'AGROQUIMICOS DEL ORIENTE',
                'razon_social' => 'AGROQUIMICOS DEL ORIENTE S.R.L.',
                'descripcion' => 'Distribución de plaguicidas y fertilizantes.',
                'estado' => 'ACTIVO',
            ],
            [
                'nombre' => 'AGROSERVICIOS DEL SUR',
                'razon_social' => 'AGROSERVICIOS DEL SUR',
                'descripcion' => 'Servicios y productos agrícolas regionales.',
                'estado' => 'INACTIVO',
            ],
        ];    
        
        foreach ($fabricantes as $elemento) {
            // El nombre es unico; se actualiza si ya fue cargado antes.
            Fabricante::updateOrCreate(
                ['nombre' => $elemento['nombre']],
                $elemento
            );
        }
    }
}

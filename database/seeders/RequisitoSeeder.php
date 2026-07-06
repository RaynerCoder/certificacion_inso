<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class RequisitoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Requisitos documentales para certificados.
     */
    public function run(): void
    {
        foreach ([
            1 => 'Solicitud firmada por representante legal',
            2 => 'Etiqueta aprobada del producto',
            3 => 'Ficha tecnica y hoja de seguridad',
            4 => 'Comprobante de pago validado',
            5 => 'Certificado de registro de plaguicida vigente',
        ] as $id => $descripcion) {
            $this->guardar('requisitos', $id, [
                'descripcion' => $descripcion,
                'estado' => $this->estado('requisitos'),
            ]);
        }
    }
}

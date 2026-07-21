<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class SeguimientoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Primer movimiento del flujo:
     * Mario Pedraza envía el trámite y este llega al jefe de Unidad UTHSI.
     */
    public function run(): void
    {
        $this->guardar('seguimientos', 1, [
            'id_seguimiento_padre' => null,
            'id_certificado' => 1,
            'fecha_inicio' => '2026-06-15',
            'fecha_derivacion' => null,
            'fecha_final' => null,
            'descripcion_final' => 'Solicitud recibida por jefatura UTHSI.',
            'referencia' => 'Inicio del trámite',
            'id_usuario_anterior' => null,
            'id_usuario_origen' => 9,
            'id_usuario_siguiente' => 4,
            'estado' => 'ACTIVO',
        ]);
    }
}

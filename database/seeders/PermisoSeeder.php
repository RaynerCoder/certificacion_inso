<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;

class PermisoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Permisos base del sistema.
     */
    public function run(): void
    {
        foreach ([
            1 => 'dashboard.ver',
            2 => 'personas.ver',
            3 => 'productos.ver',
            4 => 'certificados.emitir',
            5 => 'pagos.validar',
            6 => 'seguimientos_tramite.gestionar',
            7 => 'roles.gestionar',
            8 => 'seguimientos_tramite.iniciar',
            9 => 'seguimientos_tramite.enviados',
            10 => 'seguimientos_tramite.atender',
            11 => 'seguimientos_tramite.consulta_general',
            12 => 'seguimientos_tramite.historial',
            13 => 'territorios.ver',
            14 => 'usuarios.ver',
            15 => 'permisos.ver',
            16 => 'areas.ver',
            17 => 'cargos.ver',
            18 => 'responsables.ver',
            19 => 'tramitadores.ver',
            20 => 'tipos_empresas.ver',
            21 => 'tipos_certificados.ver',
            22 => 'requisitos.ver',
            23 => 'tipos_evidencias.ver',
            24 => 'plantillas_certificados.ver',
            25 => 'fabricantes.ver',
            26 => 'tipos_productos.ver',
            27 => 'ingredientes.ver',
            28 => 'presentaciones.ver',
            29 => 'registros.ver',
            30 => 'procedencias.ver',
            31 => 'pagos.ver',
            32 => 'certificados.ver',
            33 => 'empresas.ver',
            34 => 'personas_naturales.ver',
            35 => 'rubros.ver',
        ] as $id => $nombre) {
            $this->guardar('permisos', $id, [
                'nombre' => $nombre,
                'estado' => $this->estado('permisos'),
            ]);
        }
    }
}

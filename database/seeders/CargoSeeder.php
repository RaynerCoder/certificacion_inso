<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CargoSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Cargos base para asignar responsabilidades internas.
     */
    public function run(): void
    {
        $areas = DB::table('areas')
            ->whereNull('deleted_at')
            ->pluck('id', 'nombre');

        $cargos = [
            1 => ['Administrador del sistema', 'Gestiona configuracion, seguridad y parametros generales.', 'DIRECCION GENERAL EJECUTIVA'],
            2 => ['Tecnico evaluador', 'Revisa requisitos, productos y observaciones tecnicas.', 'UNIDAD TECNICA DE HIGIENE Y SEGURIDAD INDUSTRIAL'],
            3 => ['Responsable de pagos', 'Registra y valida pagos asociados a tramites.', 'DIRECCION GENERAL EJECUTIVA'],
            4 => ['Jefe de Unidad UTHSI', 'Jefe para la Unidad Tecnica de Higiene y Seguridad Industrial del INSO, gestion 2026.', 'UNIDAD TECNICA DE HIGIENE Y SEGURIDAD INDUSTRIAL'],
            5 => ['Quimico UTHSI', 'Funcionario tecnico quimico de UTHSI.', 'AREA DE LABORATORIO DE QUIMICA'],
            6 => ['Quimico UTHSI II', 'Funcionario tecnico quimico de UTHSI II.', 'AREA DE LABORATORIO DE QUIMICA'],
            7 => ['Quimico UTHSI III', 'Funcionario tecnico quimico de UTHSI III.', 'AREA DE LABORATORIO DE QUIMICA'],
            8 => ['Ingeniero UTHSI', 'Funcionario tecnico de ingenieria UTHSI.', 'AREA DE INGENIERIA'],
            9 => ['Secretaria UTHSI', 'Apoyo administrativo de la Unidad Tecnica de Higiene y Seguridad Industrial.', 'UNIDAD TECNICA DE HIGIENE Y SEGURIDAD INDUSTRIAL'],
            10 => ['Ingeniero Industrial UTHSI', 'Funcionario de ingenieria industrial UTHSI.', 'AREA DE INGENIERIA'],
            11 => ['Secretaria del INSO', 'Apoyo administrativo de Direccion General Ejecutiva.', 'DIRECCION GENERAL EJECUTIVA'],
            12 => ['Director General Ejecutivo a.i.', 'Autoridad de Direccion General Ejecutiva.', 'DIRECCION GENERAL EJECUTIVA'],
            13 => ['Tecnico de plaguicidas', 'Evalua registros y documentacion de plaguicidas de uso domestico.', 'AREA DE PLAGUICIDAS'],
        ];

        $this->liberarNombresDuplicados($cargos);

        foreach ($cargos as $id => [$nombre, $descripcion, $area]) {
            $this->guardar('cargos', $id, [
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'id_area' => $areas[$area] ?? null,
                'estado' => $this->estado('cargos'),
            ]);
        }
    }

    /**
     * Si existian cargos con el mismo nombre en otro ID, se marcan como historicos
     * para respetar el indice unico de cargos.nombre.
     */
    private function liberarNombresDuplicados(array $cargos): void
    {
        foreach ($cargos as $id => [$nombre]) {
            $cargoDuplicado = DB::table('cargos')
                ->where('nombre', $nombre)
                ->where('id', '!=', $id)
                ->first();

            if (!$cargoDuplicado) {
                continue;
            }

            DB::table('cargos')
                ->where('id', $cargoDuplicado->id)
                ->update([
                    'nombre' => $nombre . ' (historico ' . $cargoDuplicado->id . ')',
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        }
    }
}

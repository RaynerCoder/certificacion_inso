<?php

namespace Database\Seeders;

use Database\Seeders\Concerns\GuardaSeeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AreaSeeder extends Seeder
{
    use GuardaSeeders;

    /**
     * Areas base para organizar cargos y funcionarios del sistema.
     */
    public function run(): void
    {
        $areas = [
            1 => [null, 'DIRECCION GENERAL EJECUTIVA', 'Direccion superior institucional del INSO.'],
            2 => [1, 'UNIDAD TECNICA DE HIGIENE Y SEGURIDAD INDUSTRIAL', 'Unidad tecnica dependiente de Direccion General Ejecutiva.'],
            3 => [2, 'AREA DE LABORATORIO DE QUIMICA', 'Area tecnica de laboratorio y analisis quimico de UTHSI.'],
            4 => [2, 'AREA DE INGENIERIA', 'Area tecnica de ingenieria, inspecciones y estudios de UTHSI.'],
            5 => [2, 'AREA DE PLAGUICIDAS', 'Area tecnica de registro y control de plaguicidas de UTHSI.'],
        ];

        foreach ($areas as $id => [$idAreaPadre, $nombre, $descripcion]) {
            $this->guardar('areas', $id, [
                'id_area_padre' => $idAreaPadre,
                'nombre' => $nombre,
                'descripcion' => $descripcion,
                'estado' => $this->estado('areas'),
            ]);
        }

        // Normaliza nombres antiguos que venian como texto en cargos.area antes de existir areas.
        foreach ($this->aliasAreas() as $alias => $nombreCanonico) {
            $idAlias = DB::table('areas')->where('nombre', $alias)->value('id');
            $idCanonico = DB::table('areas')->where('nombre', $nombreCanonico)->value('id');

            if (!$idAlias || !$idCanonico || (int) $idAlias === (int) $idCanonico) {
                continue;
            }

            DB::table('cargos')
                ->where('id_area', $idAlias)
                ->update([
                    'id_area' => $idCanonico,
                    'updated_at' => now(),
                ]);

            DB::table('areas')
                ->where('id_area_padre', $idAlias)
                ->update([
                    'id_area_padre' => $idCanonico,
                    'updated_at' => now(),
                ]);

            DB::table('areas')
                ->where('id', $idAlias)
                ->update([
                    'deleted_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $nombresCanonicos = collect($areas)->map(fn ($area) => $area[1])->all();

        DB::table('areas')
            ->whereIn('nombre', $nombresCanonicos)
            ->whereNotIn('id', array_keys($areas))
            ->update([
                'deleted_at' => now(),
                'updated_at' => now(),
            ]);
    }

    /**
     * Nombres viejos que se unifican con el catalogo institucional.
     */
    private function aliasAreas(): array
    {
        return [
            'Tecnica' => 'UNIDAD TECNICA DE HIGIENE Y SEGURIDAD INDUSTRIAL',
            'UNIDAD TECNICA DE HIGIENE SEGURIDAD INDUSTRIAL' => 'UNIDAD TECNICA DE HIGIENE Y SEGURIDAD INDUSTRIAL',
            'Administrativa' => 'DIRECCION GENERAL EJECUTIVA',
            'ADMINISTRATIVA' => 'DIRECCION GENERAL EJECUTIVA',
            'AREA ADMINISTRATIVA' => 'DIRECCION GENERAL EJECUTIVA',
            'Sistemas' => 'DIRECCION GENERAL EJECUTIVA',
            'Laboratorio' => 'AREA DE LABORATORIO DE QUIMICA',
            'Plaguicidas' => 'AREA DE PLAGUICIDAS',
            'Seguridad Industrial' => 'AREA DE INGENIERIA',
            'SEGURIDAD INDUSTRIAL' => 'AREA DE INGENIERIA',
            'AREA DE SEGURIDAD INDUSTRIAL' => 'AREA DE INGENIERIA',
        ];
    }
}

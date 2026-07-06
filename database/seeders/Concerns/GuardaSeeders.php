<?php

namespace Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

trait GuardaSeeders
{
    /**
     * Inserta o actualiza una fila por ID sin duplicar datos.
     */
    protected function guardar(string $tabla, int $id, array $datos): void
    {
        $ahora = now();

        if (Schema::hasColumn($tabla, 'created_at')) {
            $datos['created_at'] = $datos['created_at'] ?? $ahora;
        }

        if (Schema::hasColumn($tabla, 'updated_at')) {
            $datos['updated_at'] = $ahora;
        }

        if (Schema::hasColumn($tabla, 'deleted_at')) {
            $datos['deleted_at'] = null;
        }

        DB::table($tabla)->updateOrInsert(['id' => $id], $datos);
    }

    /**
     * Devuelve ACTIVO/INACTIVO o 1/0 segun el tipo de la columna estado.
     */
    protected function estado(string $tabla, bool $activo = true): string|int
    {
        $tipo = Schema::hasColumn($tabla, 'estado')
            ? Schema::getColumnType($tabla, 'estado')
            : 'string';

        return in_array($tipo, ['boolean', 'integer', 'bigint', 'smallint', 'tinyint'], true)
            ? ($activo ? 1 : 0)
            : ($activo ? 'ACTIVO' : 'INACTIVO');
    }
}

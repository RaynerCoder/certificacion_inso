<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conserva los accesos existentes al separar consulta y operación.
     */
    public function up(): void
    {
        if (!Schema::hasTable('permisos')
            || !Schema::hasTable('permisos_roles')
            || !Schema::hasTable('permisos_users')) {
            return;
        }

        $this->copiarAsignaciones(
            'seguimientos_tramite.atender',
            'seguimientos_tramite.gestionar'
        );

        $this->copiarAsignaciones(
            'pagos.ver',
            'pagos.validar'
        );
    }

    /**
     * No elimina asignaciones al revertir para no borrar cambios manuales posteriores.
     */
    public function down(): void
    {
        //
    }

    /**
     * Copia a roles y usuarios el permiso específico sin generar duplicados.
     */
    private function copiarAsignaciones(string $permisoOrigen, string $permisoDestino): void
    {
        $idOrigen = DB::table('permisos')->where('nombre', $permisoOrigen)->value('id');
        $idDestino = DB::table('permisos')->where('nombre', $permisoDestino)->value('id');

        if (!$idOrigen || !$idDestino) {
            return;
        }

        $ahora = now();

        DB::table('permisos_roles')
            ->where('id_permiso', $idOrigen)
            ->pluck('id_role')
            ->each(function ($idRol) use ($idDestino, $ahora) {
                DB::table('permisos_roles')->insertOrIgnore([
                    'id_permiso' => $idDestino,
                    'id_role' => $idRol,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            });

        DB::table('permisos_users')
            ->where('id_permiso', $idOrigen)
            ->pluck('id_user')
            ->each(function ($idUsuario) use ($idDestino, $ahora) {
                DB::table('permisos_users')->insertOrIgnore([
                    'id_user' => $idUsuario,
                    'id_permiso' => $idDestino,
                    'created_at' => $ahora,
                    'updated_at' => $ahora,
                ]);
            });
    }
};

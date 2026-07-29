<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Agrega el orden configurable y conserva la secuencia actual de los registros.
     */
    public function up(): void
    {
        Schema::table('requisitos_tipos_certificados', function (Blueprint $table) {
            $table->unsignedInteger('orden')->nullable()->after('id_tipo_certificado');
            $table->index(['id_tipo_certificado', 'orden'], 'rtc_tipo_orden_index');
        });

        Schema::table('requisitos_certificados', function (Blueprint $table) {
            $table->unsignedInteger('orden')->nullable()->after('id_requisito');
            $table->index(['id_certificado', 'orden'], 'rc_certificado_orden_index');
        });

        $this->completarOrden(
            'requisitos_tipos_certificados',
            'id_tipo_certificado'
        );

        $this->completarOrden(
            'requisitos_certificados',
            'id_certificado'
        );
    }

    /**
     * Numera primero los registros vigentes y después el historial eliminado.
     */
    private function completarOrden(string $tabla, string $columnaGrupo): void
    {
        $idsGrupos = DB::table($tabla)
            ->select($columnaGrupo)
            ->distinct()
            ->orderBy($columnaGrupo)
            ->pluck($columnaGrupo);

        foreach ($idsGrupos as $idGrupo) {
            $idsRegistros = DB::table($tabla)
                ->where($columnaGrupo, $idGrupo)
                ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('id')
                ->pluck('id');

            foreach ($idsRegistros as $indice => $idRegistro) {
                DB::table($tabla)
                    ->where('id', $idRegistro)
                    ->update(['orden' => $indice + 1]);
            }
        }
    }

    /**
     * Revierte únicamente las columnas e índices agregados.
     */
    public function down(): void
    {
        Schema::table('requisitos_certificados', function (Blueprint $table) {
            $table->dropIndex('rc_certificado_orden_index');
            $table->dropColumn('orden');
        });

        Schema::table('requisitos_tipos_certificados', function (Blueprint $table) {
            $table->dropIndex('rtc_tipo_orden_index');
            $table->dropColumn('orden');
        });
    }
};

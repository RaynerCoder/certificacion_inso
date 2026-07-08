<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('naturals', function (Blueprint $table) {
            if (! Schema::hasColumn('naturals', 'id_ocupacion')) {
                $table->foreignId('id_ocupacion')->nullable()->after('genero')->constrained('ocupaciones_cob');
            }
        });

        Schema::table('rubros', function (Blueprint $table) {
            if (! Schema::hasColumn('rubros', 'descripcion')) {
                $table->text('descripcion')->nullable()->after('nombre');
            }
        });

        if (Schema::hasColumn('rubros', 'id_persona')) {
            DB::table('rubros')
                ->whereNotNull('id_persona')
                ->orderBy('id')
                ->get()
                ->each(function ($rubro) {
                    DB::table('personas_rubros')->updateOrInsert(
                        [
                            'id_persona' => $rubro->id_persona,
                            'id_rubro' => $rubro->id,
                        ],
                        [
                            'estado' => $rubro->estado ?: 'ACTIVO',
                        ]
                    );
                });

            Schema::table('rubros', function (Blueprint $table) {
                $table->dropForeign(['id_persona']);
                $table->dropColumn('id_persona');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rubros', function (Blueprint $table) {
            if (! Schema::hasColumn('rubros', 'id_persona')) {
                $table->foreignId('id_persona')->nullable()->after('id')->constrained('personas');
            }

            if (Schema::hasColumn('rubros', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
        });

        Schema::table('naturals', function (Blueprint $table) {
            if (Schema::hasColumn('naturals', 'id_ocupacion')) {
                $table->dropForeign(['id_ocupacion']);
                $table->dropColumn('id_ocupacion');
            }
        });
    }
};

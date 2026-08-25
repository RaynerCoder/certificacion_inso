<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda propiedades visuales que cambian según el tipo de bloque.
     * Por ejemplo: rotación, bordes, relleno y diseño de tablas.
     */
    public function up(): void
    {
        if (! Schema::hasTable('plantillas_elementos') || Schema::hasColumn('plantillas_elementos', 'configuracion')) {
            return;
        }

        Schema::table('plantillas_elementos', function (Blueprint $table) {
            $table->json('configuracion')->nullable()->after('z_index');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plantillas_elementos') || ! Schema::hasColumn('plantillas_elementos', 'configuracion')) {
            return;
        }

        Schema::table('plantillas_elementos', function (Blueprint $table) {
            $table->dropColumn('configuracion');
        });
    }
};

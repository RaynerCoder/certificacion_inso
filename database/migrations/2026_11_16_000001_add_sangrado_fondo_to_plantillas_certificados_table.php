<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plantillas_certificados')
            || Schema::hasColumn('plantillas_certificados', 'sangrado_fondo')) {
            return;
        }

        // Compensa el borde físico de impresoras que no imprimen a sangre.
        Schema::table('plantillas_certificados', function (Blueprint $table) {
            $table->unsignedTinyInteger('sangrado_fondo')->default(2)->after('ajuste_fondo');
        });
    }

    public function down(): void
    {
        if (Schema::hasTable('plantillas_certificados')
            && Schema::hasColumn('plantillas_certificados', 'sangrado_fondo')) {
            Schema::table('plantillas_certificados', function (Blueprint $table) {
                $table->dropColumn('sangrado_fondo');
            });
        }
    }
};

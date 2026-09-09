<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plantillas_certificados')
            || Schema::hasColumn('plantillas_certificados', 'diseno_fabric_json')) {
            return;
        }

        Schema::table('plantillas_certificados', function (Blueprint $table) {
            $table->json('diseno_fabric_json')->nullable()->after('imprimir_transparente');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('plantillas_certificados')
            || ! Schema::hasColumn('plantillas_certificados', 'diseno_fabric_json')) {
            return;
        }

        Schema::table('plantillas_certificados', function (Blueprint $table) {
            $table->dropColumn('diseno_fabric_json');
        });
    }
};

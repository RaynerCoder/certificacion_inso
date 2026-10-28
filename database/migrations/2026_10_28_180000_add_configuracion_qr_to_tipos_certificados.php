<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipos_certificados', function (Blueprint $table) {
            $table->json('configuracion_qr')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('tipos_certificados', fn (Blueprint $table) => $table->dropColumn('configuracion_qr'));
    }
};

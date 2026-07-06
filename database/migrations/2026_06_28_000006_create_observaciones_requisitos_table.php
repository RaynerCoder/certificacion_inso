<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda las observaciones asociadas a una revision tecnica especifica.
     */
    public function up(): void
    {
        if (Schema::hasTable('observaciones_requisitos')) {
            return;
        }

        Schema::create('observaciones_requisitos', function (Blueprint $table) {
            $table->id();

            // Revision exacta que origina la observacion.
            $table->foreignId('id_revision_requisito')
                ->constrained('revisiones_requisitos');

            $table->text('observacion');
            $table->string('estado', 50)->default('ACTIVA');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Elimina las observaciones nuevas por revision de requisito.
     */
    public function down(): void
    {
        Schema::dropIfExists('observaciones_requisitos');
    }
};

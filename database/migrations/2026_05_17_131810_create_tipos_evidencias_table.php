<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el catalogo que indica como se cumple un requisito:
     * PDF, imagen, texto, pago, validacion presencial o certificado vigente.
     */
    public function up(): void
    {
        if (Schema::hasTable('tipos_evidencias')) {
            return;
        }

        Schema::create('tipos_evidencias', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 100)->unique();
            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->unsignedInteger('tamanio_maximo_mb')->default(0);
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Elimina el catalogo de tipos de evidencia.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_evidencias');
    }
};

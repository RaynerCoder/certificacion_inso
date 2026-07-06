<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda cada revision tecnica realizada sobre un requisito del tramite.
     */
    public function up(): void
    {
        if (Schema::hasTable('revisiones_requisitos')) {
            return;
        }

        Schema::create('revisiones_requisitos', function (Blueprint $table) {
            $table->id();

            // Requisito revisado. Es obligatorio porque puede revisarse incluso sin evidencia cargada.
            $table->foreignId('id_requisito_certificado')
                ->constrained('requisitos_certificados');

            // Evidencia revisada. Puede ser nula cuando el requisito no tiene evidencia presentada.
            $table->foreignId('id_evidencia_requisito')
                ->nullable()
                ->constrained('evidencias_requisitos');

            // Usuario tecnico o funcionario que realizo la revision.
            $table->foreignId('id_usuario_revisor')
                ->constrained('users');

            // Resultado de la revision: SI, NO, PENDIENTE, etc.
            $table->string('resultado_cumple', 50);

            // Estado del registro de revision: ACTIVO, ANULADO, etc.
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_requisito_certificado', 'estado'], 'revisiones_requisitos_estado_index');
        });
    }

    /**
     * Elimina el historial de revisiones de requisitos.
     */
    public function down(): void
    {
        Schema::dropIfExists('revisiones_requisitos');
    }
};

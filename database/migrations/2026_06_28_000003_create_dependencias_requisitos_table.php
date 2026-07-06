<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conecta un requisito configurado con otro tipo de certificado requerido.
     * Esta tabla permite armar el arbol de certificados previos.
     */
    public function up(): void
    {
        if (Schema::hasTable('dependencias_requisitos')) {
            return;
        }

        Schema::create('dependencias_requisitos', function (Blueprint $table) {
            $table->id();

            // Requisito configurado en requisitos_tipos_certificados.
            $table->foreignId('id_requisito_tipo_certificado')
                ->constrained('requisitos_tipos_certificados');

            // Tipo de certificado que el sistema debe buscar como vigente.
            $table->foreignId('id_tipo_certificado_requerido')
                ->constrained('tipos_certificados');

            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->unique(
                ['id_requisito_tipo_certificado', 'id_tipo_certificado_requerido'],
                'dependencias_requisitos_unique'
            );
        });
    }

    /**
     * Elimina las dependencias entre requisitos y certificados previos.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependencias_requisitos');
    }
};

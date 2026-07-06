<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda la evidencia real presentada o validada para un requisito del tramite.
     */
    public function up(): void
    {
        if (Schema::hasTable('evidencias_requisitos')) {
            return;
        }

        Schema::create('evidencias_requisitos', function (Blueprint $table) {
            $table->id();

            // Requisito del tramite al que pertenece la evidencia.
            $table->foreignId('id_requisito_certificado')
                ->constrained('requisitos_certificados');

            // Tipo que indica como interpretar el valor: archivo, pago, certificado, texto, etc.
            $table->foreignId('id_tipo_evidencia')
                ->constrained('tipos_evidencias');

            // Valor unico de la evidencia:
            // PDF/IMAGEN = ruta; PAGO = id_pago; CERTIFICADO_VIGENTE = id_certificado; TEXTO/PRESENCIAL = texto.
            $table->text('valor')->nullable();

            $table->string('estado', 50)->default('REGISTRADO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_requisito_certificado', 'id_tipo_evidencia'], 'evidencias_requisitos_busqueda_index');
        });
    }

    /**
     * Elimina las evidencias de requisitos.
     */
    public function down(): void
    {
        Schema::dropIfExists('evidencias_requisitos');
    }
};

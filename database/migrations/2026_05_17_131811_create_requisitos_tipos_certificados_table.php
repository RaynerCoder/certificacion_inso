<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Tabla pivote: define que requisitos exige cada tipo de certificado.
        Schema::create('requisitos_tipos_certificados', function (Blueprint $table) {
            $table->id();

            // Requisito asociado al tipo de certificado.
            $table->foreignId('id_requisito')->constrained('requisitos');

            // Forma en que se cumple el requisito: PDF, PAGO, CERTIFICADO_VIGENTE, etc.
            $table->foreignId('id_tipo_evidencia')->nullable()->constrained('tipos_evidencias')->nullOnDelete();

            // Tipo de certificado que exige el requisito.
            $table->foreignId('id_tipo_certificado')->constrained('tipos_certificados');

            // Estado general de la relacion: ACTIVO, INACTIVO, etc.
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            // Evita duplicar el mismo requisito dentro del mismo tipo de certificado.
            $table->index(['id_tipo_certificado', 'id_requisito'], 'rtc_tipo_requisito_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitos_tipos_certificados');
    }
};

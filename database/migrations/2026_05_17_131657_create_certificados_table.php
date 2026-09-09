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
        Schema::create('certificados', function (Blueprint $table) {
            $table->id();

            // Tipo de certificado emitido.
            $table->foreignId('id_tipo_certificado')->constrained('tipos_certificados');

            // Persona o empresa beneficiaria del certificado.
            $table->foreignId('id_persona_beneficiario')->constrained('personas');

            // Persona que realiza o tramita el certificado.
            $table->foreignId('id_persona_tramitador')->constrained('personas');

            // Codigo unico visible del certificado.
            $table->string('codigo', 255)->unique();

            // Fechas de vigencia del certificado.
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();

            // Descripcion u observacion general del certificado.
            $table->text('descripcion')->nullable();

            // Ruta o URL del documento generado.
            $table->text('url_documento')->nullable();

            // Estado general del certificado: ACTIVO, VENCIDO, ANULADO, etc.
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });

        // Registra quien descargo cada certificado y cuando lo hizo.
        Schema::create('descargas_certificados', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('id_certificado');
            $table->unsignedBigInteger('id_usuario');
            $table->dateTime('fecha_descarga')->useCurrent();

            $table->index(
                ['id_certificado', 'id_usuario'],
                'idx_descargas_certificados_certificado_usuario'
            );
            $table->index('id_usuario', 'idx_descargas_certificados_usuario');

            $table->foreign('id_certificado', 'fk_descargas_certificados_certificado')
                ->references('id')
                ->on('certificados');
            $table->foreign('id_usuario', 'fk_descargas_certificados_usuario')
                ->references('id')
                ->on('users');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('descargas_certificados');
        Schema::dropIfExists('certificados');
    }
};

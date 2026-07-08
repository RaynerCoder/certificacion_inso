<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda la plantilla principal que usara cada tipo de certificado.
     */
    public function up(): void
    {
        if (Schema::hasTable('plantillas_certificados')) {
            return;
        }

        Schema::create('plantillas_certificados', function (Blueprint $table) {
            $table->id();

            // Tipo de certificado al que pertenece la plantilla.
            $table->foreignId('id_tipo_certificado')->constrained('tipos_certificados');

            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();

            // Define el formato de salida: CARTA, OFICIO o A4.
            $table->string('tamano_papel', 50)->default('CARTA');

            // Define si el documento se emitira vertical u horizontal.
            $table->string('orientacion', 50)->default('VERTICAL');

            // Ruta del fondo cargado por el usuario: imagen o PDF.
            $table->text('url_fondo')->nullable();

            // Permite tener una plantilla activa por tipo de certificado.
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_tipo_certificado', 'estado'], 'plantillas_certificados_tipo_estado_index');
        });
    }

    /**
     * Elimina las plantillas de certificados.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_certificados');
    }
};

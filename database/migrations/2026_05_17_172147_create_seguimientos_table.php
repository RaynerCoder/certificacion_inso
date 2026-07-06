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
        Schema::create('seguimientos', function (Blueprint $table) {
            $table->id();

            // Seguimiento anterior para formar el historial encadenado.
            $table->foreignId('id_seguimiento_padre')->nullable()->constrained('seguimientos');

            // Certificado al que pertenece el seguimiento.
            $table->foreignId('id_certificado')->constrained('certificados');

            // Fecha en la que inicia esta etapa del seguimiento.
            $table->date('fecha_inicio')->nullable();

            // Fecha en la que esta etapa se deriva o deja de estar pendiente.
            $table->date('fecha_derivacion')->nullable();

            // Fecha de cierre definitivo cuando el tramite ya no requiere otro paso.
            $table->date('fecha_final')->nullable();

            // Resultado o descripcion final del seguimiento.
            $table->text('descripcion_final')->nullable();

            // Referencia adicional del seguimiento.
            $table->text('referencia')->nullable();

            // Usuario que tenia el seguimiento antes de derivarlo.
            $table->foreignId('id_usuario_anterior')->nullable()->constrained('users');

            // Usuario que envia o deriva el seguimiento.
            $table->foreignId('id_usuario_origen')->nullable()->constrained('users');

            // Usuario responsable del siguiente paso.
            $table->foreignId('id_usuario_siguiente')->nullable()->constrained('users');

            // Estados manejados por ahora: ACTIVO e INACTIVO.
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
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('seguimientos');
    }
};

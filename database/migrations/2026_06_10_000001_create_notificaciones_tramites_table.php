<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabla relacional para avisar solicitudes nuevas sin guardar JSON.
     */
    public function up(): void
    {
        Schema::create('notificaciones_tramites', function (Blueprint $table) {
            $table->id();

            // Usuario que debe ver la notificacion en la campana.
            $table->foreignId('id_usuario_destino')->constrained('users');

            // Usuario que genero o envio la notificacion.
            $table->foreignId('id_usuario_emisor')->nullable()->constrained('users');

            // Tramite/certificado que origina la notificacion.
            $table->foreignId('id_certificado')->constrained('certificados');

            // Texto visible de la notificacion, separado en columnas normales.
            $table->string('titulo', 255);
            $table->text('mensaje')->nullable();

            // Fecha de lectura: null significa pendiente de ver.
            $table->timestamp('fecha_visto')->nullable();

            // Estados basicos: ACTIVO, VISTO, INACTIVO.
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificaciones_tramites');
    }
};

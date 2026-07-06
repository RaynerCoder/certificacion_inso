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
        Schema::create('tipos_certificados', function (Blueprint $table) {
            $table->id();

            // Nombre del tipo de certificado que se emitira.
            $table->string('nombre', 255);

            // Area responsable por defecto para atender este tipo de certificado.
            $table->foreignId('id_area')->nullable()->constrained('areas')->nullOnDelete();

            // Estado general del tipo de certificado: ACTIVO, INACTIVO, etc.
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
        Schema::dropIfExists('tipos_certificados');
    }
};

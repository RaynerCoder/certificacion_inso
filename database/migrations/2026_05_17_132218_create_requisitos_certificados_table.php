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
        // Tabla pivote: guarda si un certificado cumple cada requisito.
        Schema::create('requisitos_certificados', function (Blueprint $table) {
            $table->id();

            // Certificado evaluado.
            $table->foreignId('id_certificado')->constrained('certificados');

            // Requisito revisado para ese certificado.
            $table->foreignId('id_requisito')->constrained('requisitos');

            // Resultado del requisito: CUMPLE, NO CUMPLE, PENDIENTE, etc.
            $table->string('cumple', 50)->nullable();

            // Estado general del registro del requisito: ACTIVO, INACTIVO, etc.
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
        Schema::dropIfExists('requisitos_certificados');
    }
};

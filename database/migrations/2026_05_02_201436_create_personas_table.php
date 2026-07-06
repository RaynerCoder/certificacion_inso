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
        Schema::create('personas', function (Blueprint $table) {
            $table->id();

            // Cuenta propia de acceso al sistema: una persona/empresa tiene un usuario.
            $table->foreignId('id_usuario')->nullable()->unique()->constrained('users');
            $table->string('domicilio', 255)->nullable();
            $table->string('nit', 50)->nullable();
            $table->string('correo', 50)->unique();
            $table->foreignId('id_territorio')->constrained('territorios');
            // Estado general de la persona: ACTIVO, INACTIVO, etc.
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoría
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
        Schema::dropIfExists('personas');
    }
};

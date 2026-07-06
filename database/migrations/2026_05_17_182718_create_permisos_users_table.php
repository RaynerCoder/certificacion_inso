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
        Schema::create('permisos_users', function (Blueprint $table) {
            $table->id();

            // Usuario que recibe el permiso directo.
            $table->foreignId('id_user')->constrained('users');

            // Permiso asignado directamente al usuario.
            $table->foreignId('id_permiso')->constrained('permisos');

            // Evita repetir el mismo permiso directo en el mismo usuario.
            $table->unique(['id_user', 'id_permiso']);

            // Auditoria de usuario sobre asignaciones.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permisos_users');
    }
};

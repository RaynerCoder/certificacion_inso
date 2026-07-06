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
        Schema::create('roles_users', function (Blueprint $table) {
            $table->id();

            // Rol asignado al usuario.
            $table->foreignId('id_role')->constrained('roles');

            // Usuario que recibe el rol.
            $table->foreignId('id_user')->constrained('users');

            // Evita repetir el mismo rol en el mismo usuario.
            $table->unique(['id_role', 'id_user']);

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
        Schema::dropIfExists('roles_users');
    }
};

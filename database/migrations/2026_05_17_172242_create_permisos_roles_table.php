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
        Schema::create('permisos_roles', function (Blueprint $table) {
            $table->id();

            // Permiso asignado al rol.
            $table->foreignId('id_permiso')->constrained('permisos');

            // Rol que recibe el permiso.
            $table->foreignId('id_role')->constrained('roles');

            // Evita repetir el mismo permiso en el mismo rol.
            $table->unique(['id_permiso', 'id_role']);

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
        Schema::dropIfExists('permisos_roles');
    }
};

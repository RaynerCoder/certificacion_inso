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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            // Nombre visible del rol, por ejemplo: Administrador.
            $table->string('name', 255);

            // Codigo interno del rol, por ejemplo: admin.
            $table->string('slug', 255)->unique();

            // Descripcion corta del alcance del rol.
            $table->text('descripcion')->nullable();

            // Marca especial para roles del sistema o de uso restringido. Como por ejemplo dar acceso a todo (ALL)
            $table->string('especial', 255)->nullable();

            // Estado numerico segun el modelo de seguridad: 1 activo, 0 inactivo.
            $table->tinyInteger('estado')->comment('1 activo, 0 inactivo')->default(1);

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
        Schema::dropIfExists('roles');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('funcionarios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_usuario')->unique()->constrained('users');

            $table->string('nombres', 255);
            $table->string('apellido_paterno', 255);
            $table->string('apellido_materno', 255)->nullable();
            $table->string('carnet', 50)->unique();
            $table->string('telefono', 50)->nullable();
            $table->tinyInteger('genero')->comment('1 masculino, 0 femenino');
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
     * Elimina la ficha de funcionarios.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionarios');
    }
};

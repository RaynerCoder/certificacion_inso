<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('cargos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255)->unique();
            $table->text('descripcion')->nullable();
            $table->foreignId('id_area')->nullable()->constrained('areas')->nullOnDelete();
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
     * Elimina el catalogo de cargos.
     */
    public function down(): void
    {
        Schema::dropIfExists('cargos');
    }
};

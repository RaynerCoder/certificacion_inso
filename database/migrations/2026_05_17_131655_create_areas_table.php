<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el catalogo jerarquico de areas institucionales.
     */
    public function up(): void
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_area_padre')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('nombre', 255)->unique();
            $table->text('descripcion')->nullable();
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
     * Elimina el catalogo de areas.
     */
    public function down(): void
    {
        Schema::dropIfExists('areas');
    }
};

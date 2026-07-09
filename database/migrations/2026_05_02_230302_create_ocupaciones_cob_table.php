<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el catalogo oficial de ocupaciones COB usado por personas naturales.
     */
    public function up(): void
    {
        Schema::create('ocupaciones_cob', function (Blueprint $table) {
            $table->id();
            $table->string('codigo_gran_grupo', 20)->nullable();
            $table->text('descripcion_gran_grupo')->nullable();
            $table->string('codigo_subgrupo_principal', 20)->nullable();
            $table->text('descripcion_subgrupo_principal')->nullable();
            $table->string('codigo_subgrupo', 20)->nullable();
            $table->text('descripcion_subgrupo')->nullable();
            $table->string('codigo_grupo_primario', 20)->nullable();
            $table->text('descripcion_grupo_primario')->nullable();
            $table->string('codigo_ocupacion', 20)->nullable();
            $table->text('descripcion_ocupacion');

            // Auditoria
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Elimina el catalogo de ocupaciones COB.
     */
    public function down(): void
    {
        Schema::dropIfExists('ocupaciones_cob');
    }
};

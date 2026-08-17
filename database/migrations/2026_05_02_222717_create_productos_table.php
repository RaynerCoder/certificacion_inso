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
        Schema::create('productos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_importador_persona')->constrained('personas');
            $table->string('codigo', 150)->nullable();
            $table->foreignId('id_territorio_pais')->constrained('territorios');
            $table->foreignId('id_fabricante')->constrained('fabricantes');
            $table->string('nombre_comercial', 255)->nullable();
            $table->string('nombre_tecnico', 255)->nullable();
            $table->foreignId('id_clasificacion_quimica')->nullable()->constrained('clasificaciones_quimicas');
            $table->foreignId('id_clasificacion_toxicologica')->constrained('clasificaciones_toxicologicas');
            // Estados manejados por ahora: ACTIVO e INACTIVO.
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
        Schema::dropIfExists('productos');
    }
};

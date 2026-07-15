<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda las columnas de los elementos tipo TABLA.
     */
    public function up(): void
    {
        if (Schema::hasTable('plantillas_columnas')) {
            return;
        }

        Schema::create('plantillas_columnas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_plantilla_elemento')->constrained('plantillas_elementos');

            // Codigo controlado que se resolvera por cada fila: producto.nombre_comercial, registro.codigo, etc.
            $table->string('codigo_campo', 150);
            $table->string('titulo_columna', 150);
            $table->decimal('ancho', 10, 2)->default(120);
            $table->unsignedInteger('orden')->default(1);
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_plantilla_elemento', 'estado'], 'plantillas_columnas_elemento_estado_index');
        });
    }

    /**
     * Elimina las columnas de plantillas.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_columnas');
    }
};

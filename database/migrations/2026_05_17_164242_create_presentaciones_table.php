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
        Schema::create('presentaciones', function (Blueprint $table) {
            $table->id();

            // Producto al que pertenece la presentacion.
            $table->foreignId('id_producto')->constrained('productos');

            // URL o ruta de la etiqueta del producto.
            $table->text('url_etiqueta')->nullable();

            // Cantidad y unidad comercial de la presentacion.
            $table->integer('cantidad')->nullable();
            $table->string('unidad', 50)->nullable();

            // Descripcion adicional de la presentacion.
            $table->text('descripcion')->nullable();

            // Estados manejados por ahora: ACTIVO e INACTIVO.
            $table->string('estado', 50)->default('ACTIVO');

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
        Schema::dropIfExists('presentaciones');
    }
};

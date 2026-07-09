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
        Schema::create('registros', function (Blueprint $table) {
            $table->id();

            // Producto al que pertenece el registro.
            $table->foreignId('id_producto')->constrained('productos');

            // Codigo de autorizacion del registro.
            $table->string('codigo_autorizacion', 255)->nullable();

            // Fecha de vigencia del registro.
            $table->date('fecha_vigencia')->nullable();

            // Cantidad y unidad autorizada.
            $table->integer('cantidad')->nullable();
            $table->foreignId('id_catalogo_unidad')->nullable()->constrained('catalogos_medidas');

            // Presentacion relacionada al registro.
            $table->foreignId('id_presentacion')->nullable()->constrained('presentaciones');

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
        Schema::dropIfExists('registros');
    }
};

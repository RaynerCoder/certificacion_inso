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
        Schema::create('aduanas', function (Blueprint $table) {
            $table->id();

            // Datos de cotizacion y solicitud de la aduana.
            $table->string('codigo_cotizacion', 255)->nullable();
            $table->string('index_solicitud', 255)->nullable();
            $table->string('codigo_solicitud', 255)->nullable();

            // Datos operativos del tramite aduanero.
            $table->string('nombre_operativo', 255)->nullable();
            $table->string('acta_int', 255)->nullable();
            $table->string('item', 255)->nullable();

            // Caracteristicas del producto declarado.
            $table->text('caracteristica')->nullable();
            $table->string('marca', 255)->nullable();
            $table->date('vencimiento')->nullable();
            $table->string('unidad', 50)->nullable();
            $table->string('medida', 50)->nullable();
            $table->string('peso', 50)->nullable();
            $table->text('observacion')->nullable();

            // Producto relacionado con la aduana.
            $table->foreignId('id_producto')->constrained('productos');

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
        Schema::dropIfExists('aduanas');
    }
};

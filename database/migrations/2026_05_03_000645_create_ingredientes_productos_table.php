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
        Schema::create('ingredientes_productos', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('id_ingrediente')->constrained('ingredientes');
            $table->foreignId('id_producto')->constrained('productos');
            $table->integer('porcentaje')->nullable();
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
        Schema::dropIfExists('ingredientes_productos');
    }
};

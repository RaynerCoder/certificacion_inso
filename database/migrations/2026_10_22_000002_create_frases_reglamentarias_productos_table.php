<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la relación muchos a muchos entre productos y frases reglamentarias.
     */
    public function up(): void
    {
        Schema::create('frases_reglamentarias_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_frase_reglamentaria')->constrained('frases_reglamentarias');
            $table->foreignId('id_producto')->constrained('productos');
            $table->string('estado', 50)->default('ACTIVO');

            // Una misma frase se relaciona una sola vez con el mismo producto.
            $table->unique(
                ['id_frase_reglamentaria', 'id_producto'],
                'frases_reglamentarias_productos_frase_producto_unique'
            );

            // Auditoría
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frases_reglamentarias_productos');
    }
};

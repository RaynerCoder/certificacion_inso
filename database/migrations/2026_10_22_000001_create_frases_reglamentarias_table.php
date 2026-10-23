<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea el catálogo oficial de frases de riesgo y seguridad.
     * No se ejecuta automáticamente: se entrega junto con el SQL manual.
     */
    public function up(): void
    {
        Schema::create('frases_reglamentarias', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20);
            $table->string('codigo', 50);
            $table->text('descripcion');
            $table->string('estado', 50)->default('ACTIVO');

            // Un código oficial identifica una única frase en todo el catálogo.
            $table->unique('codigo', 'frases_reglamentarias_codigo_unique');

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
        Schema::dropIfExists('frases_reglamentarias');
    }
};

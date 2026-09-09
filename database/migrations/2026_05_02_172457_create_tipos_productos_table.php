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
        Schema::create('clasificaciones_toxicologicas', function (Blueprint $table) {
            $table->id();

            $table->string('codigo', 150);
            $table->string('descripcion', 255);
            $table->string('estado', 50)->nullable();

            // El código puede repetirse; lo que identifica la clasificación es la combinación completa.
            $table->unique(
                ['codigo', 'descripcion'],
                'clasificaciones_toxicologicas_codigo_descripcion_unique'
            );

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
        Schema::dropIfExists('clasificaciones_toxicologicas');
    }
};

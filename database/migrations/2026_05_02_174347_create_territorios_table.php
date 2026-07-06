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
        Schema::create('territorios', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_ambito')
                ->constrained('ambitos')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('id_padre_territorio')
                ->nullable()
                ->constrained('territorios')
                ->nullOnDelete() // Si se elimina el territorio padre, el hijo NO se elimina, solo queda sin padre (NULL)
                ->cascadeOnUpdate(); // Si cambia el ID del padre, se actualiza automáticamente en los hijos

            $table->string('nombre', 255);
            $table->string('codigo', 150)->unique()->nullable();
            // Estados manejados por ahora: ACTIVO e INACTIVO.
            $table->string('estado', 50)->default('ACTIVO');

            // Evita duplicados en el mismo nivel
            $table->unique(['id_padre_territorio', 'nombre']);

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
        Schema::dropIfExists('territorios');
    }
};

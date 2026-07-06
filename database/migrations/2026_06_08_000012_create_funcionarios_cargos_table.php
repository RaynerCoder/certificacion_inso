<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la relacion muchos a muchos entre funcionarios y cargos.
     */
    public function up(): void
    {
        Schema::create('funcionarios_cargos', function (Blueprint $table) {
            $table->id();

            // Funcionario al que se le asigna el cargo.
            $table->foreignId('id_funcionario')->constrained('funcionarios');

            // Cargo asignado al funcionario.
            $table->foreignId('id_cargo')->constrained('cargos');

            // Evita asignar dos veces el mismo cargo al mismo funcionario.
            $table->unique(['id_funcionario', 'id_cargo']);

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();            
        });
    }

    /**
     * Elimina la relacion entre funcionarios y cargos.
     */
    public function down(): void
    {
        Schema::dropIfExists('funcionarios_cargos');
    }
};

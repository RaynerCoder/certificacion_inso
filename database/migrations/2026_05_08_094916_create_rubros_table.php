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
        Schema::create('rubros', function (Blueprint $table) {
            $table->id();

            // Jerarquia oficial CAEB-2022:
            // SECCION -> DIVISION -> GRUPO -> CLASE -> SUBCLASE.
            $table->foreignId('id_rubro_padre')
                ->nullable()
                ->constrained('rubros')
                ->cascadeOnUpdate()
                ->nullOnDelete();
            $table->string('codigo_caeb', 5)->nullable();
            $table->string('nivel_caeb', 20)->nullable();

            $table->string('nombre', 255);
            $table->text('descripcion')->nullable();
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->unique('codigo_caeb', 'rubros_codigo_caeb_unique');
            $table->index('nivel_caeb', 'rubros_nivel_caeb_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubros');
    }
};

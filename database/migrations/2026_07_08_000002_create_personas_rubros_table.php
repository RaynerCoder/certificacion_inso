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
        Schema::create('personas_rubros', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_persona')->constrained('personas');
            $table->foreignId('id_rubro')->constrained('rubros');
            $table->string('estado', 50)->default('ACTIVO');

            // Evita que una persona tenga dos veces el mismo rubro.
            $table->unique(['id_persona', 'id_rubro'], 'personas_rubros_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personas_rubros');
    }
};

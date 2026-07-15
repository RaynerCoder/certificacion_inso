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
        Schema::create('catalogos_medidas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->string('abreviatura', 50)->nullable();
            $table->string('tipo', 50)->default('unidad de medida');
            $table->string('estado', 50)->default('ACTIVO');
            $table->timestamps();
            $table->softDeletes();

            $table->unique('nombre', 'catalogos_medidas_nombre_unique');
            $table->unique('abreviatura', 'catalogos_medidas_abreviatura_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('catalogos_medidas');
    }
};

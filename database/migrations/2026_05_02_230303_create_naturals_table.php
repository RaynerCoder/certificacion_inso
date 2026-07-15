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
        Schema::create('naturals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_persona')->constrained('personas');
            $table->string('ci', 50)->nullable();
            $table->string('complemento', 10)->nullable();
            $table->string('expedido', 10)->nullable();
            $table->string('nombres', 100);
            $table->string('apellido_paterno', 100);
            $table->string('apellido_materno', 100)->nullable();
            $table->string('apellido_casado', 100)->nullable();
            $table->dateTime('fecha_nacimiento')->nullable();
            $table->tinyInteger('genero')->comment('1=masculino, 0=femenino');
            $table->foreignId('id_ocupacion_cob')->nullable()->constrained('ocupaciones_cob');

            // Auditoria
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
        Schema::dropIfExists('naturals');
    }
};

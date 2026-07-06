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
        Schema::create('responsables', function (Blueprint $table) {
            $table->id();

            $table->foreignId('id_empresa')->constrained('empresas');
            $table->foreignId('id_persona')->constrained('personas');
            $table->foreignId('id_rol')->constrained('roles');
            $table->text('url_respaldo')->nullable();
            $table->date('fecha_registro')->nullable();
            $table->date('fecha_baja')->nullable();
            $table->string('estado', 50)->nullable();
            
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
        Schema::dropIfExists('responsables');
    }
};

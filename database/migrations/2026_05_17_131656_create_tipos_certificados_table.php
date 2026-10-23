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
        Schema::create('tipos_certificados', function (Blueprint $table) {
            $table->id();

            // Nombre del tipo de certificado que se emitira.
            $table->string('nombre', 255);

            // Area responsable por defecto para atender este tipo de certificado.
            $table->foreignId('id_area')->nullable()->constrained('areas')->nullOnDelete();

            // Funcionario configurado para recibir inicialmente los tramites de este tipo.
            // La llave foranea se agrega al crear funcionarios porque esa tabla se crea despues.
            $table->unsignedBigInteger('id_funcionario_receptor_inicial')->nullable()->index();

            // Cantidad de descargas permitidas por usuario. Nulo significa sin limite.
            $table->unsignedInteger('limite_descargas')->nullable();

            // Precio que se cobrará por este tipo de certificado.
            $table->decimal('costo', 10, 2)->nullable();

            // Regla de vigencia: por ejemplo 30_DIAS, 6_MESES, 4_ANIOS o INDEFINIDA.
            $table->string('vigencia', 30)->nullable();
            
            // Estado general del tipo de certificado: ACTIVO, INACTIVO, etc.
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->unique('nombre', 'tipos_certificados_nombre_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tipos_certificados');
    }
};

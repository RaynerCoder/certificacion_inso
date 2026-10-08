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
        Schema::create('pagos_openbcb', function (Blueprint $table) {
            $table->id();

            // Pago de nuestro sistema para el cual se generó el QR.
            // No usamos unique porque un pago podría necesitar otro QR si el anterior vence.
            $table->foreignId('id_pago')->constrained('pagos');

            // Identificador que OpenBCB devuelve al generar el QR.
            $table->string('id_qr', 100)->nullable()->unique();

            
            // Ruta donde guardaremos la imagen del QR.
            // Ejemplo: openbcb/qr/QR-123456.jpg
            $table->string('imagen_qr_path', 255)->nullable();     
            

            // Auditoria de usuario sobre altas, cambios y bajas.
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
        Schema::dropIfExists('pagos_openbcb');
    }
};

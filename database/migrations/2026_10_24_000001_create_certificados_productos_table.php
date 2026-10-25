<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Relaciona el producto solicitado con su trámite, aunque todavía no tenga
     * registros o lotes creados.
     */
    public function up(): void
    {
        Schema::create('certificados_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('id_certificado')->constrained('certificados');
            $table->foreignId('id_producto')->constrained('productos');

            // Un producto se vincula una sola vez con un mismo trámite.
            $table->unique(
                ['id_certificado', 'id_producto'],
                'certificados_productos_certificado_producto_unique'
            );

            // Auditoría
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificados_productos');
    }
};

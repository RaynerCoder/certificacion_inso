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
        Schema::create('pagos', function (Blueprint $table) {
            $table->id();

            // Procedencia u origen administrativo del pago.
            $table->foreignId('id_procedencia')->constrained('procedencias');

            // Tipo de pago realizado por el cliente.
            $table->string('tipo_pago', 100);

            // Fecha en la que se registra el pago.
            $table->date('fecha');

            // Comprobante o respaldo del pago.
            $table->string('comprobante', 255)->nullable();

            // Monto total pagado.
            $table->decimal('monto', 10, 2);

            // Persona que realiza o solicita el pago.
            $table->foreignId('id_cliente')->constrained('personas');

            // Usuario que valida o atiende el pago.
            $table->foreignId('id_funcionario')->nullable()->constrained('users');

            // Fecha de validacion del pago.
            $table->date('fecha_validacion')->nullable();

            // Numero o codigo de factura asociada.
            $table->string('factura', 100)->nullable();

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
        Schema::dropIfExists('pagos');
    }
};

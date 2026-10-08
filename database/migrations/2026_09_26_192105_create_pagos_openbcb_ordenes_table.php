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
        Schema::create('pagos_openbcb_ordenes', function (Blueprint $table) {
            $table->id();

            // OpenBCB envía estos datos:

            // Relaciona esta orden con el QR guardado en pagos_openbcb.
            $table->foreignId('id_pago_openbcb')->constrained('pagos_openbcb');

            // Identificador de la operación bancaria enviado por OpenBCB.
            $table->string('id_orden_destinatario', 100)->unique();

            // Moneda utilizada en el pago. Normalmente será BOB.
            $table->string('cod_moneda', 3);

            // Monto pagado informado por OpenBCB.
            $table->decimal('importe', 15, 2);

            // Fecha y hora de la operación enviada por OpenBCB.
            $table->dateTime('fecha');

            // CI o NIT del titular de la cuenta que realizó el pago.
            $table->string('ci_nit_originante', 30)->nullable();

            // Nombre del titular que realizó el pago.
            $table->string('nombre_originante', 255)->nullable();

            // Cuenta bancaria desde donde salió el dinero.
            $table->string('cuenta_origen', 100)->nullable();

            // Código de la entidad financiera desde donde salió el dinero.
            $table->string('eif_origen', 50)->nullable();

            // Estado de la orden informado por OpenBCB.
            $table->string('estado', 50);

            // Código EIF recibido mediante la notificación de OpenBCB.
            $table->string('eif', 50)->nullable();

            // Tipo de notificación. La documentación muestra T1 como ejemplo.
            $table->string('tipo_notificacion', 20)->nullable();

            // Información adicional recibida en metaData.
            $table->json('metadata')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pagos_openbcb_ordenes');
    }
};

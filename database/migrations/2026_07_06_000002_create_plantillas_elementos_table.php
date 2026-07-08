<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarda cada campo, texto, tabla, firma o QR colocado sobre la plantilla.
     */
    public function up(): void
    {
        if (Schema::hasTable('plantillas_elementos')) {
            return;
        }

        Schema::create('plantillas_elementos', function (Blueprint $table) {
            $table->id();

            // Plantilla a la que pertenece el elemento.
            $table->foreignId('id_plantilla_certificado')->constrained('plantillas_certificados');

            // TEXTO, CAMPO, TABLA, FIRMA, QR o IMAGEN.
            $table->string('tipo_elemento', 50);

            // Codigo controlado que el backend resolvera al emitir: beneficiario.nombre, producto.tabla, etc.
            $table->string('codigo_campo', 150)->nullable();

            // Texto fijo cuando el elemento no depende de un dato de la base de datos.
            $table->text('texto_fijo')->nullable();

            $table->unsignedInteger('pagina')->default(1);
            $table->decimal('posicion_x', 10, 2)->default(0);
            $table->decimal('posicion_y', 10, 2)->default(0);
            $table->decimal('ancho', 10, 2)->default(180);
            $table->decimal('alto', 10, 2)->default(30);
            $table->unsignedInteger('tamano_letra')->default(12);
            $table->string('alineacion', 50)->default('IZQUIERDA');
            $table->boolean('negrita')->default(false);
            $table->boolean('cursiva')->default(false);
            $table->boolean('subrayado')->default(false);
            $table->string('color_texto', 20)->default('#0f172a');
            $table->unsignedInteger('orden')->default(1);
            $table->string('estado', 50)->default('ACTIVO');

            // Auditoria de usuario sobre altas, cambios y bajas.
            $table->foreignId('id_usuario_registro')->nullable()->constrained('users');
            $table->foreignId('id_usuario_modificacion')->nullable()->constrained('users');
            $table->foreignId('id_usuario_eliminacion')->nullable()->constrained('users');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['id_plantilla_certificado', 'estado'], 'plantillas_elementos_plantilla_estado_index');
        });
    }

    /**
     * Elimina los elementos de plantillas.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas_elementos');
    }
};

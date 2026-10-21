<?php

namespace Tests\Feature;

use App\Http\Controllers\TipoCertificadoController;
use App\Models\TipoCertificado;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TipoCertificadoEvidenciaObligatoriaTest extends TestCase
{
    public function test_no_permite_guardar_requisitos_sin_tipo_de_evidencia(): void
    {
        foreach ([[], ['id_tipo_evidencia' => null], ['id_tipo_evidencia' => '']] as $requisito) {
            $solicitud = Request::create('/', 'PUT', [
                'form_modo_emision' => array_key_first(TipoCertificado::MODOS_EMISION),
                'form_siguiente_correlativo' => 1,
                'requisitos_asignados' => [array_merge(['descripcion' => 'Documento de respaldo'], $requisito)],
            ]);

            try {
                app(TipoCertificadoController::class)->actualizarConfiguracion($solicitud, new TipoCertificado());
                $this->fail('Debe rechazar el requisito sin evidencia.');
            } catch (ValidationException $exception) {
                $this->assertSame(
                    ['Seleccione el tipo de evidencia para este requisito.'],
                    $exception->errors()['requisitos_asignados.0.id_tipo_evidencia'] ?? null
                );
            }
        }
    }
}

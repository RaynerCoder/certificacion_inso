<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
use App\Models\Certificado;
use App\Models\EvidenciaRequisito;
use App\Models\RequisitoCertificado;
use App\Models\TipoEvidencia;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ValidacionCorreccionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_bloquea_cualquier_evidencia_obligatoria_faltante(): void
    {
        foreach (['PDF', 'IMAGEN', 'TEXTO', 'PAGO', 'CERTIFICADO', 'DESCONOCIDO'] as $codigo) {
            try {
                $this->validar($codigo, [], [123 => '   ']);
                $this->fail('Se permitió enviar sin evidencia: '.$codigo);
            } catch (ValidationException $e) {
                $this->assertNotEmpty($e->errors(), $codigo);
            }
        }
    }

    public function test_permite_archivos_y_texto_nuevos_y_no_exige_archivos_para_atencion_interna(): void
    {
        foreach (['PDF', 'IMAGEN'] as $codigo) {
            $this->validar($codigo, [123 => UploadedFile::fake()->create('correccion.pdf', 10)], []);
        }
        $this->validar('TEXTO', [], [123 => 'Información corregida']);
        foreach (['PRODUCTO', 'PRESENCIAL', 'CALIFICACION', 'TRAMITADOR'] as $codigo) {
            $this->validar($codigo, [], []);
        }
        $this->addToAssertionCount(7);
    }

    private function validar(string $codigo, array $archivos, array $textos): void
    {
        $certificado = new Certificado(['id_tipo_certificado' => 999, 'id_persona_beneficiario' => 999]);
        $certificado->id = 999;
        $requisito = new RequisitoCertificado(['id_requisito' => 999]);
        $requisito->id = 123;
        $requisito->setRelation('requisito', null);
        $evidencia = new EvidenciaRequisito(['valor' => null]);
        $evidencia->id = 1;
        $evidencia->setRelation('tipoEvidencia', new TipoEvidencia(['codigo' => $codigo]));
        $requisito->setRelation('evidenciasRequisitos', new Collection([$evidencia]));
        (new \ReflectionMethod(SeguimientoController::class, 'validarCorreccionesAntesDeDevolver'))
            ->invoke(app(SeguimientoController::class), $certificado, collect([$requisito]), $archivos, $textos);
    }
}

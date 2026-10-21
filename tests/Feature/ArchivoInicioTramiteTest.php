<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
use App\Models\RequisitoTipoCertificado;
use App\Models\TipoEvidencia;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ArchivoInicioTramiteTest extends TestCase
{
    public function test_reconoce_el_pdf_en_el_indice_cero_y_en_indices_no_consecutivos(): void
    {
        foreach ([0, 4] as $indice) {
            $request = Request::create('/', 'POST', [
                'requisitos_certificados' => [$indice => ['id_requisito_tipo_certificado' => 288]],
            ], [], [
                'documentos_requisitos' => [$indice => UploadedFile::fake()->createWithContent('respaldo.pdf', "%PDF-1.4\n%%EOF")],
            ]);
            $this->validar($request);
            $this->addToAssertionCount(1);
        }
    }

    public function test_rechaza_un_archivo_que_no_corresponde_al_requisito(): void
    {
        $request = Request::create('/', 'POST', [
            'requisitos_certificados' => [0 => ['id_requisito_tipo_certificado' => 289]],
        ], [], [
            'documentos_requisitos' => [0 => UploadedFile::fake()->createWithContent('respaldo.pdf', "%PDF-1.4\n%%EOF")],
        ]);
        $this->expectException(ValidationException::class);
        $this->validar($request);
    }

    private function validar(Request $request): void
    {
        $asignacion = new RequisitoTipoCertificado();
        $asignacion->id = 288;
        $asignacion->setRelation('requisito', null);
        $asignacion->setRelation('tipoEvidencia', new TipoEvidencia(['codigo' => 'PDF', 'tamanio_maximo_mb' => 30]));
        (new \ReflectionMethod(SeguimientoController::class, 'validarArchivosSubidosDeRequisitos'))
            ->invoke(app(SeguimientoController::class), $request, collect([$asignacion]));
    }
}

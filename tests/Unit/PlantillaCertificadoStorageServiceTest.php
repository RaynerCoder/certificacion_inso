<?php

namespace Tests\Unit;

use App\Models\PlantillaCertificado;
use App\Services\PlantillaCertificadoStorageService;
use Tests\TestCase;

class PlantillaCertificadoStorageServiceTest extends TestCase
{
    public function test_organiza_el_archivo_directamente_dentro_de_su_plantilla(): void
    {
        $plantilla = new PlantillaCertificado;
        $plantilla->id = 27;
        $plantilla->id_tipo_certificado = 9;

        $directorio = app(PlantillaCertificadoStorageService::class)->directorio($plantilla);

        $this->assertSame('plantillas/9-certificado', $directorio);
    }
}

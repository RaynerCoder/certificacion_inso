<?php

namespace Tests\Unit;

use App\Models\PlantillaCertificado;
use App\Services\PlantillaCertificadoStorageService;
use PHPUnit\Framework\TestCase;

class PlantillaCertificadoStorageServiceTest extends TestCase
{
    public function test_organiza_el_archivo_directamente_dentro_de_su_plantilla(): void
    {
        $plantilla = new PlantillaCertificado;
        $plantilla->id = 27;

        $directorio = (new PlantillaCertificadoStorageService)->directorio($plantilla);

        $this->assertSame('plantillas/certificados/27', $directorio);
    }
}

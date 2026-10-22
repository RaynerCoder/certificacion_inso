<?php

namespace Tests\Unit;

use App\Services\ArchivoPrivadoService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ArchivoPrivadoServiceTest extends TestCase
{
    public function test_normaliza_rutas_historicas_sin_duplicar_la_raiz_privada(): void
    {
        $archivos = app(ArchivoPrivadoService::class);
        $rutaEsperada = 'personas-empresas/empresas/1/representantes-legales/3-responsable-respaldo.pdf';

        foreach ([
            $rutaEsperada,
            'private/'.$rutaEsperada,
            'app/private/'.$rutaEsperada,
            'storage/app/private/'.$rutaEsperada,
            '/var/www/certificador/storage/app/private/'.$rutaEsperada,
            'C:\\sitio\\storage\\app\\private\\'.$rutaEsperada,
        ] as $ruta) {
            $this->assertSame($rutaEsperada, $archivos->normalizarNullable($ruta));
        }
    }

    public function test_encuentra_un_archivo_privado_aunque_la_ruta_incluya_private(): void
    {
        Storage::fake('local');

        $archivos = app(ArchivoPrivadoService::class);
        $ruta = 'personas-empresas/empresas/1/representantes-legales/3-responsable-respaldo.pdf';
        Storage::disk('local')->put($ruta, 'pdf');

        $this->assertTrue($archivos->existe('private/'.$ruta));
        $this->assertTrue($archivos->existe('storage/app/private/'.$ruta));
    }
}

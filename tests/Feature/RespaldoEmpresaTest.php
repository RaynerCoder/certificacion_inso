<?php

namespace Tests\Feature;

use App\Http\Controllers\PersonaController;
use App\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RespaldoEmpresaTest extends TestCase
{
    use RefreshDatabase;

    public function test_separa_pdf_por_empresa_y_funcion_y_conserva_temporales(): void
    {
        Storage::fake('local');
        $representante = Role::create(['name' => 'Representante legal', 'slug' => 'solicitante', 'estado' => 1]);
        $tramitador = Role::create(['name' => 'Tramitador', 'slug' => 'tramitador', 'estado' => 1]);
        $controlador = app(PersonaController::class);
        $metodo = new \ReflectionMethod($controlador, 'guardarRespaldoResponsable');

        foreach ([
            [12, $representante->id, 'personas-empresas/empresas/12/representantes-legales'],
            [12, $tramitador->id, 'tramitadores/empresas/12'],
            [34, $representante->id, 'personas-empresas/empresas/34/representantes-legales'],
            [null, $representante->id, 'personas-empresas/empresas/pendientes/representantes-legales'],
        ] as [$empresa, $rol, $prefijo]) {
            $ruta = $metodo->invoke($controlador, UploadedFile::fake()->create('respaldo.pdf', 10, 'application/pdf'), $empresa, $rol);
            $this->assertStringStartsWith($prefijo, $ruta);
            Storage::disk('local')->assertExists($ruta);
        }
    }

    public function test_el_formulario_no_convierte_un_respaldo_privado_en_url_publica(): void
    {
        $controlador = file_get_contents(app_path('Http/Controllers/PersonaController.php'));
        $middleware = file_get_contents(app_path('Http/Middleware/VerificarPermiso.php'));
        $scripts = file_get_contents(resource_path('views/personas/create/scripts.blade.php'));

        $this->assertStringContainsString("'url_respaldo_ver' =>", $controlador);
        $this->assertStringContainsString("route('responsables_respaldo', \$responsable)", $controlador);
        $this->assertStringContainsString('datos.urlRespaldoVer', $scripts);
        $this->assertStringNotContainsString('`storage/${rutaLimpia}`', $scripts);
        $this->assertStringContainsString("'responsables_respaldo' => [", $middleware);
        $this->assertStringContainsString("'personas.ver',", $middleware);
    }
}

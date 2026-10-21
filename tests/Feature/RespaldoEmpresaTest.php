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
        Storage::fake('public');
        $representante = Role::create(['name' => 'Representante legal', 'slug' => 'solicitante', 'estado' => 1]);
        $tramitador = Role::create(['name' => 'Tramitador', 'slug' => 'tramitador', 'estado' => 1]);
        $controlador = app(PersonaController::class);
        $metodo = new \ReflectionMethod($controlador, 'guardarRespaldoResponsable');

        foreach ([
            [12, $representante->id, 'empresas-tramitadores/12/representantes-legales/'],
            [12, $tramitador->id, 'empresas-tramitadores/12/tramitadores/'],
            [34, $representante->id, 'empresas-tramitadores/34/representantes-legales/'],
            [null, $representante->id, 'empresas-tramitadores/pendientes/representantes-legales/'],
        ] as [$empresa, $rol, $prefijo]) {
            $ruta = $metodo->invoke($controlador, UploadedFile::fake()->create('respaldo.pdf', 10, 'application/pdf'), $empresa, $rol);
            $this->assertStringStartsWith($prefijo, $ruta);
            Storage::disk('public')->assertExists($ruta);
        }
    }
}

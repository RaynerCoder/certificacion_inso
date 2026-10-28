<?php

namespace Tests\Feature;

use App\Models\TipoCertificado;
use App\Http\Controllers\TipoCertificadoController;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ConfiguracionQrPersistenciaTest extends TestCase
{
    public function test_los_selectores_son_visibles_sin_activar_qr_y_conservan_los_datos_tras_un_error(): void
    {
        $this->app['request']->setLaravelSession($this->app['session']->driver());
        $tipo = new TipoCertificado;
        $tipo->id = 2;
        $datos = ['tipoCertificado' => $tipo, 'errors' => new \Illuminate\Support\ViewErrorBag];
        $html = view('tipos_certificados.configuracion_qr', $datos)->render();
        $this->assertStringContainsString('name="form_qr_campos[0][campo]"', $html);
        $this->assertStringContainsString('Agregar dato público', $html);
        $this->assertStringNotContainsString('x-cloak', $html);
        session()->flashInput([
            'form_qr_habilitado' => '1',
            'form_qr_campos' => [['campo' => 'evaluacion.calificaciones', 'etiqueta' => 'Nota final']],
        ]);
        $html = view('tipos_certificados.configuracion_qr', $datos)->render();
        $this->assertMatchesRegularExpression('/value="1"\s+checked/', $html);
        $this->assertStringContainsString('value="Nota final"', $html);
        $this->assertStringContainsString('value="evaluacion.calificaciones" selected', $html);
    }

    public function test_rechaza_campos_no_publicos_duplicados_y_qr_activo_sin_datos(): void
    {
        foreach ([
            [[], 'form_qr_campos'],
            [[['campo' => 'beneficiario.correo', 'etiqueta' => 'Correo']], 'form_qr_campos.0.campo'],
            [[['campo' => 'estado', 'etiqueta' => 'Estado'], ['campo' => 'estado', 'etiqueta' => 'Otro']], 'form_qr_campos.0.campo'],
        ] as [$campos, $error]) {
            try {
                app(TipoCertificadoController::class)->actualizarConfiguracion(Request::create('/', 'PUT', [
                    'form_qr_habilitado' => '1', 'form_qr_campos' => $campos,
                ]), new TipoCertificado);
                $this->fail('Debe rechazar la configuración QR inválida.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey($error, $e->errors());
            }
        }
    }

    public function test_la_migracion_y_el_modelo_conservan_la_configuracion_al_reabrir(): void
    {
        Schema::create('tipos_certificados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->timestamps();
            $table->softDeletes();
        });
        $migracion = require database_path('migrations/2026_10_28_180000_add_configuracion_qr_to_tipos_certificados.php');
        $migracion->up();
        $configuracion = ['habilitado' => true, 'titulo' => 'Verificación de capacitación', 'campos' => [
            ['campo' => 'evaluacion.calificaciones', 'etiqueta' => 'Nota'],
            ['campo' => 'beneficiario.nombre', 'etiqueta' => 'Participante'],
        ]];
        $tipo = TipoCertificado::withoutEvents(fn () => TipoCertificado::create([
            'nombre' => 'Capacitación', 'configuracion_qr' => $configuracion,
        ]));
        $this->assertSame($configuracion, $tipo->fresh()->configuracionQr());
        $migracion->down();
        $this->assertFalse(Schema::hasColumn('tipos_certificados', 'configuracion_qr'));
    }
}

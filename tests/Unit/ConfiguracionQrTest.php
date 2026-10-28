<?php

namespace Tests\Unit;

use App\Http\Controllers\CertificadoController;
use App\Models\Certificado;
use App\Models\PlantillaCertificado;
use App\Models\PlantillaElemento;
use App\Models\TipoCertificado;
use Illuminate\Database\Eloquent\Collection;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class ConfiguracionQrTest extends TestCase
{
    public function test_ningun_tipo_activa_qr_sin_configuracion_guardada(): void
    {
        $tipo = new TipoCertificado;
        $tipo->id = 1;
        $this->assertFalse($tipo->configuracionQr()['habilitado']);
        $this->assertCount(5, $tipo->configuracionQr()['campos']);
        $tipo->id = 2;
        $this->assertFalse($tipo->configuracionQr()['habilitado']);
    }

    public function test_respeta_el_orden_las_etiquetas_y_los_valores_de_cada_certificado(): void
    {
        $tipo = new TipoCertificado(['configuracion_qr' => [
            'habilitado' => true, 'titulo' => 'Capacitación',
            'campos' => [
                ['campo' => 'evaluacion.calificaciones', 'etiqueta' => 'Nota final'],
                ['campo' => 'beneficiario.nombre', 'etiqueta' => 'Participante'],
            ],
        ]]);
        $this->assertSame([
            ['etiqueta' => 'Nota final', 'valor' => '85'],
            ['etiqueta' => 'Participante', 'valor' => 'María'],
        ], $tipo->filasPublicasQr(['evaluacion.calificaciones' => 85, 'beneficiario.nombre' => 'María']));
        $this->assertSame('Ana', $tipo->filasPublicasQr(['beneficiario.nombre' => 'Ana'])[1]['valor']);
    }

    public function test_no_expone_campos_ajenos_al_catalogo_publico(): void
    {
        $tipo = new TipoCertificado(['configuracion_qr' => ['campos' => [
            ['campo' => 'beneficiario.correo', 'etiqueta' => 'Correo'],
            ['campo' => 'estado', 'etiqueta' => ''],
        ]]]);
        $this->assertSame([['etiqueta' => 'Estado del certificado', 'valor' => 'ANULADO']],
            $tipo->filasPublicasQr(['beneficiario.correo' => 'privado@example.com', 'estado' => 'ANULADO']));
    }

    public function test_habilita_cualquier_tipo_solo_con_ubicacion_qr_visible(): void
    {
        $tipo = new TipoCertificado(['configuracion_qr' => ['habilitado' => true]]);
        $tipo->id = 7;
        $certificado = new Certificado(['id_tipo_certificado' => 7]);
        $certificado->setRelation('tipoCertificado', $tipo);
        $ubicacion = new PlantillaElemento([
            'tipo_elemento' => 'QR', 'codigo_elemento' => 'qr.verificacion',
            'texto_fijo' => 'data:image/png;base64,referencia', 'configuracion' => ['visible' => true],
        ]);
        $plantilla = new PlantillaCertificado;
        $plantilla->setRelation('elementosActivos', new Collection([$ubicacion]));
        $controller = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $metodo = new ReflectionMethod($controller, 'usaQrVerificacion');
        $this->assertTrue($metodo->invoke($controller, $certificado, $plantilla));
        $ubicacion->configuracion = ['visible' => false];
        $this->assertFalse($metodo->invoke($controller, $certificado, $plantilla));
        $ubicacion->configuracion = ['visible' => true];
        $tipo->configuracion_qr = ['habilitado' => false];
        $this->assertFalse($metodo->invoke($controller, $certificado, $plantilla));
    }

    public function test_el_documento_usa_el_qr_real_y_no_la_imagen_de_referencia(): void
    {
        $controller = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $metodo = new ReflectionMethod($controller, 'elementoDocumentoEmitido');
        $elemento = new PlantillaElemento([
            'tipo_elemento' => 'QR', 'codigo_elemento' => 'qr.verificacion',
            'texto_fijo' => 'data:image/png;base64,referencia',
        ]);
        $documento = $metodo->invoke($controller, $elemento, ['qr.verificacion' => 'data:image/svg+xml;base64,real'], []);
        $this->assertSame('data:image/svg+xml;base64,real', $documento['valor']);
    }
}

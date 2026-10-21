<?php

namespace Tests\Unit;

use App\Http\Controllers\CertificadoController;
use App\Models\PlantillaElemento;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class CertificadoPlantillaConfiguracionTest extends TestCase
{
    #[DataProvider('configuracionesDeTabla')]
    public function test_normaliza_los_estilos_de_tabla_antes_de_guardarlos(
        array $entrada,
        array $esperado
    ): void {
        $metodo = new ReflectionMethod(CertificadoController::class, 'normalizarConfiguracionElementoPlantilla');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $resultado = $metodo->invoke($controlador, $entrada, 'TABLA');

        foreach ($esperado as $propiedad => $valor) {
            $this->assertSame($valor, $resultado['tabla'][$propiedad]);
        }
    }

    public static function configuracionesDeTabla(): array
    {
        return [
            'sin bordes y filas alternas' => [
                [
                    'tabla' => [
                        'bordes' => 'NINGUNO',
                        'filas_alternas' => true,
                        'padding_x' => 10,
                        'alineacion_vertical' => 'INFERIOR',
                    ],
                ],
                [
                    'bordes' => 'NINGUNO',
                    'filas_alternas' => true,
                    'padding_x' => 10.0,
                    'alineacion_vertical' => 'INFERIOR',
                ],
            ],
            'valores fuera de rango' => [
                [
                    'tabla' => [
                        'bordes' => 'NO_EXISTE',
                        'borde_ancho' => 50,
                        'padding_y' => -4,
                    ],
                ],
                [
                    'bordes' => 'TODOS',
                    'borde_ancho' => 8,
                    'padding_y' => 0,
                ],
            ],
        ];
    }

    public function test_descarta_configuracion_visual_no_admitida(): void
    {
        $metodo = new ReflectionMethod(CertificadoController::class, 'normalizarConfiguracionElementoPlantilla');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $resultado = $metodo->invoke($controlador, [
            'rotacion' => 900,
            'opacidad' => 0,
            'fondo_color' => 'javascript:alert(1)',
            'borde_estilo' => 'inventado',
            'propiedad_no_admitida' => 'no debe guardarse',
        ], 'FORMA');

        $this->assertSame(360, $resultado['rotacion']);
        $this->assertSame(0.05, $resultado['opacidad']);
        $this->assertSame('transparent', $resultado['fondo_color']);
        $this->assertSame('SOLIDO', $resultado['borde_estilo']);
        $this->assertArrayNotHasKey('propiedad_no_admitida', $resultado);
    }

    public function test_conserva_los_datos_del_panel_de_capas(): void
    {
        $metodo = new ReflectionMethod(CertificadoController::class, 'normalizarConfiguracionElementoPlantilla');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $resultado = $metodo->invoke($controlador, [
            'nombre_capa' => 'Nombre del beneficiario',
            'grupo' => 'grupo_172345',
            'visible' => false,
            'bloqueado' => true,
        ], 'TEXTO');

        $this->assertSame('Nombre del beneficiario', $resultado['nombre_capa']);
        $this->assertSame('grupo_172345', $resultado['grupo']);
        $this->assertFalse($resultado['visible']);
        $this->assertTrue($resultado['bloqueado']);
    }

    public function test_las_capas_nuevas_son_visibles_por_defecto(): void
    {
        $metodo = new ReflectionMethod(CertificadoController::class, 'normalizarConfiguracionElementoPlantilla');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $resultado = $metodo->invoke($controlador, [], 'CAMPO');

        $this->assertTrue($resultado['visible']);
        $this->assertFalse($resultado['bloqueado']);
    }

    public function test_conserva_solo_estilos_de_texto_permitidos(): void
    {
        $metodo = new ReflectionMethod(CertificadoController::class, 'normalizarConfiguracionElementoPlantilla');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $resultado = $metodo->invoke($controlador, [
            'estilos_texto' => [
                0 => [
                    2 => ['fontWeight' => 'bold', 'fill' => 'javascript:alert(1)'],
                    3 => ['fontStyle' => 'inventado'],
                    4 => ['underline' => true],
                ],
            ],
        ], 'TEXTO');

        $this->assertSame(['fontWeight' => 'bold'], $resultado['estilos_texto'][0][2]);
        $this->assertArrayNotHasKey(3, $resultado['estilos_texto'][0]);
        $this->assertSame(['underline' => true], $resultado['estilos_texto'][0][4]);
    }

    public function test_renderiza_negrita_parcial_sin_permitir_html_del_usuario(): void
    {
        $elemento = new PlantillaElemento([
            'texto_fijo' => 'Hola <mundo>',
            'configuracion' => [
                'estilos_texto' => [
                    0 => [
                        6 => ['fontWeight' => 'bold'],
                        7 => ['fontWeight' => 'bold'],
                        8 => ['fontWeight' => 'bold'],
                        9 => ['fontWeight' => 'bold'],
                        10 => ['fontWeight' => 'bold'],
                    ],
                ],
            ],
        ]);

        $html = $elemento->textoConEstilos()->toHtml();

        $this->assertStringContainsString('&lt;', $html);
        $this->assertStringNotContainsString('<mundo>', $html);
        $this->assertStringContainsString('<span style="font-weight:700">mundo</span>', $html);
    }

    public function test_renderiza_un_solo_salto_por_cada_enter_del_editor(): void
    {
        $elemento = new PlantillaElemento([
            'texto_fijo' => "Primera línea\nSegunda línea",
            'configuracion' => [],
        ]);

        $html = $elemento->textoConEstilos()->toHtml();

        $this->assertSame('Primera línea<br>Segunda línea', $html);
        $this->assertStringNotContainsString("\n", $html);
    }

    public function test_conserva_los_estilos_al_reemplazar_un_marcador_por_un_valor_de_otra_longitud(): void
    {
        $texto = 'Por haber {{resultado}} en el curso especial';
        $inicioMarcador = mb_strpos($texto, '{{resultado}}');
        $inicioEspecial = mb_strpos($texto, 'especial');
        $estilos = [];

        foreach (range($inicioMarcador, $inicioMarcador + mb_strlen('{{resultado}}') - 1) as $indice) {
            $estilos[0][$indice] = ['fontWeight' => 'bold'];
        }
        foreach (range($inicioEspecial, $inicioEspecial + mb_strlen('especial') - 1) as $indice) {
            $estilos[0][$indice] = ['fontStyle' => 'italic'];
        }

        $elemento = new PlantillaElemento([
            'texto_fijo' => $texto,
            'configuracion' => ['estilos_texto' => $estilos],
        ]);

        $html = $elemento->textoConEstilosResueltos(['resultado' => 'APROBADO'])->toHtml();

        $this->assertStringContainsString('<span style="font-weight:700">APROBADO</span>', $html);
        $this->assertStringContainsString('<span style="font-style:italic">especial</span>', $html);
        $this->assertStringNotContainsString('<span style="font-weight:700">curso', $html);
    }

    public function test_solo_acepta_una_imagen_png_con_la_proporcion_de_la_plantilla(): void
    {
        $metodo = new ReflectionMethod(CertificadoController::class, 'validarImagenRenderizada');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $ihdr = pack('NNCCCCC', 1056, 816, 8, 2, 0, 0, 0);
        $png = "\x89PNG\r\n\x1a\n"
            .pack('N', strlen($ihdr)).'IHDR'.$ihdr.pack('N', crc32('IHDR'.$ihdr))
            .pack('N', 0).'IEND'.pack('N', crc32('IEND'));
        $dataUrl = 'data:image/png;base64,'.base64_encode($png);

        $this->assertSame($dataUrl, $metodo->invoke($controlador, $dataUrl, 1056, 816));
        $this->assertNull($metodo->invoke($controlador, $dataUrl, 816, 1056));
        $this->assertNull($metodo->invoke($controlador, 'data:text/html;base64,PGgxPk5vPC9oMT4=', 1056, 816));
    }

    public function test_expone_calificaciones_como_campos_dinamicos_de_la_plantilla(): void
    {
        $metodo = new ReflectionMethod(CertificadoController::class, 'camposDisponiblesPlantilla');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $codigos = collect($metodo->invoke($controlador))
            ->flatten(1)
            ->pluck('codigo');

        $this->assertTrue($codigos->contains('evaluacion.calificaciones'));
        $this->assertTrue($codigos->contains('evaluacion.calificaciones_minimas'));
        $this->assertFalse($codigos->contains('evaluacion.notas'));
        $this->assertFalse($codigos->contains('evaluacion.notas_minimas'));
    }

    public function test_expone_los_datos_existentes_del_certificado_de_producto(): void
    {
        $metodo = new ReflectionMethod(CertificadoController::class, 'camposDisponiblesPlantilla');
        $controlador = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        $codigos = collect($metodo->invoke($controlador))
            ->flatten(1)
            ->pluck('codigo');

        foreach ([
            'emision.numero_certificado',
            'emision.fecha',
            'empresa.representante_legal_nombre',
            'empresa.representante_legal_documento',
            'importador.nombre',
            'importador.documento',
            'importador.ubicacion',
            'importador.representante_legal_nombre',
            'importador.representante_legal_documento',
            'producto.pais_origen',
            'fabricante.razon_social',
            'clasificacion_toxicologica.completa',
            'presentacion.lista',
            'ingrediente.composiciones',
            'ingrediente.resumen',
            'funcionario.emisor_nombre',
            'funcionario.emisor_cargo',
        ] as $codigo) {
            $this->assertTrue($codigos->contains($codigo), "No se publicó el campo dinámico {$codigo}.");
        }
    }
}

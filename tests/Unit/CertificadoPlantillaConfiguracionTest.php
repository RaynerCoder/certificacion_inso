<?php

namespace Tests\Unit;

use App\Http\Controllers\CertificadoController;
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
}

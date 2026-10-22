<?php

namespace Tests\Unit;

use App\Models\Seguimiento;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SeguimientoEstadoVisualTest extends TestCase
{
    public static function movimientos(): array
    {
        return [
            'finalizado aunque mencione observaciones' => [
                'FINALIZADO',
                'Se verificó la subsanación de las observaciones.',
                null,
                'Finalizado',
                'finalizado',
            ],
            'corrección pendiente transferida conserva su estado activo' => [
                'ACTIVO',
                'Correccion transferida al representante legal por baja del tramitador.',
                null,
                'Activo',
                'activo',
            ],
            'movimiento activo normal' => [
                'ACTIVO',
                null,
                null,
                'Activo',
                'activo',
            ],
            'referencia de derivación conserva su estado activo' => [
                'ACTIVO',
                null,
                'Derivación al laboratorio',
                'Activo',
                'activo',
            ],
            'observado guardado expresamente' => [
                'OBSERVADO',
                'Pendiente de corrección',
                null,
                'Observado',
                'observado',
            ],
        ];
    }

    #[DataProvider('movimientos')]
    public function test_respeta_el_estado_real_del_movimiento(
        string $estado,
        ?string $descripcion,
        ?string $referencia,
        string $etiquetaEsperada,
        string $filtroEsperado
    ): void {
        $seguimiento = new Seguimiento([
            'estado' => $estado,
            'descripcion_final' => $descripcion,
            'referencia' => $referencia,
        ]);

        $visual = $seguimiento->estadoVisualHistorial();

        $this->assertSame($etiquetaEsperada, $visual['label']);
        $this->assertSame($filtroEsperado, $visual['filter']);
    }
}

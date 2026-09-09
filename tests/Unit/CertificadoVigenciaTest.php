<?php

namespace Tests\Unit;

use App\Models\Certificado;
use Tests\TestCase;

class CertificadoVigenciaTest extends TestCase
{
    public function test_un_certificado_no_emitido_no_vence_aunque_tenga_una_fecha_anterior(): void
    {
        $certificado = new Certificado([
            'estado' => 'APROBADO',
            'fecha_fin' => now()->subDay(),
        ]);

        $this->assertFalse($certificado->debeMarcarseComoVencido());
    }

    public function test_un_certificado_emitido_vence_despues_de_su_fecha_final(): void
    {
        $certificado = new Certificado([
            'estado' => 'EMITIDO',
            'fecha_fin' => now()->subDay(),
        ]);

        $this->assertTrue($certificado->debeMarcarseComoVencido());
    }

    public function test_un_certificado_emitido_sigue_vigente_durante_su_fecha_final(): void
    {
        $certificado = new Certificado([
            'estado' => 'EMITIDO',
            'fecha_fin' => now(),
        ]);

        $this->assertFalse($certificado->debeMarcarseComoVencido());
    }
}

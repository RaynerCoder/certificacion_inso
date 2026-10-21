<?php

namespace Tests\Feature;

use App\Models\Certificado;
use App\Models\Empresa;
use App\Models\Pago;
use App\Models\PagoOpenBcb;
use App\Models\Persona;
use App\Models\TipoCertificado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PagoQrVistaTest extends TestCase
{
    use RefreshDatabase;

    public function test_qr_vigente_se_muestra_grande_sin_pedir_generarlo_otra_vez(): void
    {
        $this->vistaPago(false, false)
            ->assertSee('Ver QR grande')->assertSee('Descargar QR')
            ->assertSee('Código QR bancario del trámite 7')
            ->assertDontSee('id="botonGenerarQr"', false);
    }

    public function test_qr_vencido_permite_generar_otro_sin_mostrar_la_imagen_anterior(): void
    {
        $this->vistaPago(false, true)
            ->assertSee('El QR anterior venció')->assertSee('Generar código QR')
            ->assertDontSee('Código QR bancario del trámite 7');
    }

    public function test_pago_confirmado_no_invita_a_realizar_un_segundo_pago(): void
    {
        $this->vistaPago(true, false)
            ->assertSee('El pago está confirmado')
            ->assertDontSee('id="botonGenerarQr"', false)
            ->assertDontSee('Código QR bancario del trámite 7');
    }

    private function vistaPago(bool $confirmado, bool $vencido)
    {
        $this->actingAs(User::factory()->create(['estado' => 1]));
        $beneficiario = new Persona;
        $beneficiario->setRelation('empresa', new Empresa(['razon_social' => 'EMPRESA DE PRUEBA']));
        $beneficiario->setRelation('natural', null);
        $certificado = new Certificado;
        $certificado->id = 7;
        $certificado->setRelation('beneficiario', $beneficiario);
        $certificado->setRelation('tipoCertificado', new TipoCertificado(['nombre' => 'Certificado de prueba']));
        $pago = new Pago(['monto' => 100, 'fecha_validacion' => $confirmado ? now() : null]);
        $pago->id = 8;
        $ultimoQrOpenBcb = new PagoOpenBcb(['imagen_qr_path' => 'prueba.png']);
        $ultimoQrOpenBcb->id = 9;
        $ultimoQrOpenBcb->created_at = $vencido ? now()->subDays(2) : now();

        return $this->withViewErrors([])->view('seguimientos_certificados.nuevo_tramite.pagos.index',
            compact('certificado', 'pago', 'ultimoQrOpenBcb'));
    }
}

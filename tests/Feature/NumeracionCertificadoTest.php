<?php

namespace Tests\Feature;

use App\Http\Controllers\CertificadoController;
use App\Models\TipoCertificado;
use Carbon\CarbonImmutable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use ReflectionClass;
use ReflectionMethod;
use Tests\TestCase;

class NumeracionCertificadoTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Schema::create('tipos_certificados', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('prefijo_numero_certificado')->nullable();
            $table->integer('siguiente_correlativo')->default(1);
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('certificados_emitidos', function (Blueprint $table) {
            $table->id();
            $table->string('numero_certificado')->unique();
            $table->softDeletes();
        });
    }

    public function test_usa_la_fecha_de_finalizacion_sin_rellenar_el_correlativo_con_ceros(): void
    {
        $tipo = new TipoCertificado(['prefijo_numero_certificado' => 'CAP-']);
        $this->travelTo(CarbonImmutable::parse('2026-11-02'));
        $this->assertSame('CAP-10-2026-1', $tipo->formarNumeroCertificado(CarbonImmutable::parse('2026-10-30'), 1));
        $this->assertSame('CAP-10-2026-1234', $tipo->formarNumeroCertificado(CarbonImmutable::parse('2026-10-30'), 1234));
        $tipo->prefijo_numero_certificado = null;
        $this->assertSame('10-2026-1', $tipo->formarNumeroCertificado(CarbonImmutable::parse('2026-10-30'), 1));
    }

    public function test_reserva_el_numero_y_continua_el_correlativo_al_cambiar_el_mes(): void
    {
        $tipo = $this->tipo();
        $this->assertSame('CAP-10-2026-1', $this->reservar($tipo, '2026-10-30', 'CAP-10-2026-1'));
        $this->assertSame(2, $tipo->fresh()->siguiente_correlativo);
        $this->assertSame('CAP-11-2026-2', $this->reservar($tipo, '2026-11-01', 'CAP-11-2026-2'));
        $this->assertSame(3, $tipo->fresh()->siguiente_correlativo);
    }

    public function test_evade_numeros_existentes_incluso_anulados_o_eliminados(): void
    {
        $tipo = $this->tipo();
        DB::table('certificados_emitidos')->insert([
            ['numero_certificado' => 'CAP-10-2026-1', 'deleted_at' => now()],
            ['numero_certificado' => 'NUMERO-ANTERIOR-001', 'deleted_at' => null],
        ]);
        $this->assertSame('CAP-10-2026-2', $this->reservar($tipo, '2026-10-30', 'CAP-10-2026-2'));
        $this->assertTrue(DB::table('certificados_emitidos')->where('numero_certificado', 'NUMERO-ANTERIOR-001')->exists());
    }

    public function test_rechaza_una_imagen_con_numero_desactualizado_sin_consumir_correlativo(): void
    {
        $tipo = $this->tipo();
        $tipo->siguiente_correlativo = 2;
        TipoCertificado::withoutEvents(fn () => $tipo->save());
        try {
            $this->reservar($tipo, '2026-10-30', 'CAP-10-2026-1');
            $this->fail('Debe rechazar el número preparado por otra emisión.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('form_numero_certificado_preparado', $e->errors());
        }
        $this->assertSame(2, $tipo->fresh()->siguiente_correlativo);
    }

    private function tipo(): TipoCertificado
    {
        return TipoCertificado::withoutEvents(fn () => TipoCertificado::create([
            'nombre' => 'Capacitación', 'prefijo_numero_certificado' => 'CAP', 'siguiente_correlativo' => 1,
        ]));
    }

    private function reservar(TipoCertificado $tipo, string $fecha, string $preparado): string
    {
        $controller = (new ReflectionClass(CertificadoController::class))->newInstanceWithoutConstructor();
        return TipoCertificado::withoutEvents(fn () => DB::transaction(fn () =>
            (new ReflectionMethod($controller, 'siguienteNumeroCertificado'))->invoke(
                $controller, $tipo, CarbonImmutable::parse($fecha), $preparado
            )
        ));
    }
}

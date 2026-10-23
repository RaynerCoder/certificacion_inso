<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
use App\Http\Controllers\TipoCertificadoController;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Funcionario;
use App\Models\TipoCertificado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class DestinoInicialTramiteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // La base SQLite de pruebas no incluye este campo existente en la instalación local.
        \Illuminate\Support\Facades\Schema::table('funcionarios', function ($table) {
            $table->unsignedTinyInteger('estado')->default(1);
        });

        // Estos campos existen en la instalación y son requeridos por la configuración completa.
        if (! \Illuminate\Support\Facades\Schema::hasColumn('tipos_certificados', 'modo_emision')) {
            \Illuminate\Support\Facades\Schema::table('tipos_certificados', function ($table) {
                $table->string('modo_emision', 30)->nullable();
                $table->string('prefijo_numero_certificado', 100)->nullable();
                $table->unsignedBigInteger('siguiente_correlativo')->default(1);
            });
        }
    }

    public function test_no_envia_a_padres_ni_hermanos_si_el_area_no_tiene_receptor(): void
    {
        $padre = Area::create(['nombre' => 'Padre', 'estado' => 1]);
        $area = Area::create(['nombre' => 'Área del certificado', 'id_area_padre' => $padre->id, 'estado' => 1]);
        $hermana = Area::create(['nombre' => 'Hermana', 'id_area_padre' => $padre->id, 'estado' => 1]);
        $this->crearReceptor($padre, 'DIRECTOR');
        $this->crearReceptor($hermana, 'JEFE');

        foreach ([false, true] as $requierePago) {
            try {
                $this->destino($area, $requierePago);
                $this->fail('No debe aceptar un trámite sin receptor en su área.');
            } catch (ValidationException $e) {
                $this->assertSame(
                    ['El tipo de certificado no tiene un funcionario receptor activo configurado para el área inicial.'],
                    $e->errors()['form_id_tipo_certificado']
                );
            }
        }
    }

    public function test_recibe_el_funcionario_activo_del_area_del_certificado(): void
    {
        $area = Area::create(['nombre' => 'Área del certificado', 'estado' => 1]);
        $usuario = $this->crearReceptor($area, 'JEFE');
        [$destino, $receptor] = $this->destino($area, false, $usuario);
        $this->assertSame($area->id, $destino->id);
        $this->assertSame($usuario->id, $receptor->id);
        $this->assertSame([null, null], $this->destino($area, true, $usuario));
    }

    public function test_usa_el_receptor_configurado_cuando_el_area_tiene_varios_funcionarios(): void
    {
        $area = Area::create(['nombre' => 'Área compartida', 'estado' => 1]);
        $primerUsuario = $this->crearReceptor($area, 'JEFE');
        $usuarioConfigurado = $this->crearReceptor($area, 'TÉCNICO');

        [, $receptor] = $this->destino($area, false, $usuarioConfigurado);

        $this->assertNotSame($primerUsuario->id, $receptor->id);
        $this->assertSame($usuarioConfigurado->id, $receptor->id);
    }

    public function test_guarda_el_funcionario_receptor_inicial_en_el_tipo_de_certificado(): void
    {
        $area = Area::create(['nombre' => 'Área de recepción', 'estado' => 1]);
        $usuario = $this->crearReceptor($area, 'RESPONSABLE DE RECEPCIÓN');
        $tipo = TipoCertificado::create(['nombre' => 'Certificado configurado', 'estado' => 'ACTIVO']);

        $this->actingAs(User::factory()->create(['estado' => 1]));

        $vistaConfiguracion = app(TipoCertificadoController::class)->configurar($tipo);
        $opcionReceptor = $vistaConfiguracion->getData()['funcionariosReceptores']->first();
        $this->assertSame((string) $usuario->funcionario->id, $opcionReceptor['id']);
        $this->assertSame('RESPONSABLE DE RECEPCIÓN', $opcionReceptor['cargo']);

        $solicitud = Request::create('/', 'PUT', [
            'form_id_area' => $area->id,
            'form_id_funcionario_receptor_inicial' => $usuario->funcionario->id,
            'form_modo_emision' => 'UNICO_POR_TRAMITE',
            'form_vigencia_cantidad' => 4,
            'form_vigencia_unidad' => 'ANIO',
            'form_costo' => '250,50',
            'form_siguiente_correlativo' => 1,
        ]);

        app(TipoCertificadoController::class)->actualizarConfiguracion($solicitud, $tipo);

        if ($error = session('error')) {
            $this->fail($error);
        }

        $tipo->refresh();
        $this->assertSame($area->id, $tipo->id_area);
        $this->assertSame($usuario->funcionario->id, $tipo->id_funcionario_receptor_inicial);
        $this->assertSame('4_ANIO', $tipo->vigencia);
        $this->assertSame('250.50', $tipo->costo);
    }

    private function destino(Area $area, bool $requierePago, ?User $receptor = null): array
    {
        $tipo = new TipoCertificado();
        $tipo->id_funcionario_receptor_inicial = $receptor?->funcionario?->id;
        $tipo->setRelation('area', $area);
        return (new \ReflectionMethod(SeguimientoController::class, 'obtenerDestinoInicialDelTramite'))
            ->invoke(app(SeguimientoController::class), $tipo, $requierePago);
    }

    private function crearReceptor(Area $area, string $nombreCargo): User
    {
        $usuario = User::factory()->create(['estado' => 1]);
        $funcionario = new Funcionario([
            'id_usuario' => $usuario->id, 'nombres' => 'Funcionario', 'apellido_paterno' => 'Prueba',
            'ci' => (string) $usuario->id, 'genero' => 1,
        ]);
        $funcionario->estado = 1;
        $funcionario->save();
        $cargo = Cargo::create(['id_area' => $area->id, 'nombre' => $nombreCargo, 'estado' => 1]);
        $funcionario->cargos()->attach($cargo->id);
        return $usuario;
    }
}

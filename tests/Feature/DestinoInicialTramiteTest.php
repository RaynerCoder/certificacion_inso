<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Funcionario;
use App\Models\TipoCertificado;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
                $this->assertSame(['No se tiene un responsable para atender el trámite.'], $e->errors()['form_id_tipo_certificado']);
            }
        }
    }

    public function test_recibe_el_funcionario_activo_del_area_del_certificado(): void
    {
        $area = Area::create(['nombre' => 'Área del certificado', 'estado' => 1]);
        $usuario = $this->crearReceptor($area, 'JEFE');
        [$destino, $receptor] = $this->destino($area, false);
        $this->assertSame($area->id, $destino->id);
        $this->assertSame($usuario->id, $receptor->id);
        $this->assertSame([null, null], $this->destino($area, true));
    }

    private function destino(Area $area, bool $requierePago): array
    {
        $tipo = new TipoCertificado();
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

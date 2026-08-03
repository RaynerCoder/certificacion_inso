<?php

namespace Tests\Feature;

use App\Models\Ambito;
use App\Models\Empresa;
use App\Models\Funcionario;
use App\Models\Natural;
use App\Models\Permiso;
use App\Models\Persona;
use App\Models\Responsable;
use App\Models\Role;
use App\Models\Territorio;
use App\Models\TipoEmpresa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TramitadorEmpresaTest extends TestCase
{
    use RefreshDatabase;

    public function test_el_selector_entrega_la_fecha_de_nacimiento_de_la_persona_existente(): void
    {
        [$usuarioEmpresa, , $territorio] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'persona.fecha@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '4455667',
            'nombres' => 'PERSONA',
            'apellido_paterno' => 'CON FECHA',
            'fecha_nacimiento' => '1992-04-18',
            'genero' => 1,
        ]);

        $this->actingAs($usuarioEmpresa)
            ->get(route('tramitadores_create'))
            ->assertOk()
            ->assertSee('data-fecha="1992-04-18"', false);
    }

    public function test_reutiliza_persona_y_cuenta_existentes_sin_crear_credenciales(): void
    {
        Storage::fake('local');
        [$usuarioEmpresa, $empresa, $territorio] = $this->crearEmpresaConPermisos();
        $usuarioTramitador = User::factory()->create(['estado' => 1]);
        $persona = Persona::create([
            'id_usuario' => $usuarioTramitador->id,
            'correo' => 'tramitador.existente@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        $natural = Natural::create([
            'id_persona' => $persona->id,
            'ci' => '7788990',
            'nombres' => 'PERSONA',
            'apellido_paterno' => 'EXISTENTE',
            'fecha_nacimiento' => '1988-04-18',
            'genero' => 1,
        ]);

        $usuariosAntes = User::count();
        $personasAntes = Persona::count();

        $respuesta = $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_id_persona' => $persona->id,
            'form_nombres' => 'NOMBRE ALTERADO',
            'form_fecha_nacimiento' => '2000-01-01',
            'form_url_respaldo' => UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'),
        ]);

        $respuesta->assertRedirect(route('tramitadores_index'));
        $respuesta->assertSessionHas('swal', fn (array $mensaje) =>
            $mensaje['title'] === 'Solicitud de tramitador registrada'
            && str_contains($mensaje['html'], $empresa->razon_social)
            && str_contains($mensaje['html'], 'Su usuario y contraseña actuales no cambiarán')
        );
        $this->assertSame($usuariosAntes, User::count());
        $this->assertSame($personasAntes, Persona::count());
        $this->assertSame('PERSONA', $natural->fresh()->nombres);
        $this->assertSame('1988-04-18', substr($natural->fresh()->fecha_nacimiento, 0, 10));

        $relacion = Responsable::where('id_empresa', $empresa->id)
            ->where('id_persona', $persona->id)
            ->firstOrFail();

        $this->assertSame('PENDIENTE_VALIDACION', $relacion->estado);
        $this->assertSame(now()->format('Y-m-d H:i'), substr($relacion->fecha_registro, 0, 16));
        Storage::disk('local')->assertExists($relacion->url_respaldo);
    }

    public function test_crea_solo_la_ficha_personal_si_el_ci_no_existe(): void
    {
        Storage::fake('local');
        [$usuarioEmpresa, $empresa, $territorio] = $this->crearEmpresaConPermisos();
        $usuariosAntes = User::count();

        $respuesta = $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_ci' => '9900112',
            'form_nombres' => 'Nueva',
            'form_apellido_paterno' => 'Persona',
            'form_correo' => 'nueva.persona@example.com',
            'form_id_territorio' => $territorio->id,
            'form_genero' => '0',
            'form_url_respaldo' => UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'),
        ]);

        $respuesta->assertRedirect(route('tramitadores_index'));
        $respuesta->assertSessionHas('swal', fn (array $mensaje) =>
            str_contains($mensaje['html'], '<strong>Usuario:</strong> nueva.persona@example.com')
            && str_contains($mensaje['html'], '<strong>Contraseña:</strong> 9900112')
        );
        $this->assertSame($usuariosAntes, User::count());

        $natural = Natural::where('ci', '9900112')->firstOrFail();
        $this->assertNull($natural->persona->id_usuario);
        $this->assertDatabaseHas('responsables', [
            'id_empresa' => $empresa->id,
            'id_persona' => $natural->id_persona,
            'estado' => 'PENDIENTE_VALIDACION',
        ]);
    }

    public function test_empresa_reutiliza_la_misma_relacion_al_enviar_un_nuevo_respaldo(): void
    {
        Storage::fake('local');
        [$usuarioEmpresa, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'duplicado@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '1122334',
            'nombres' => 'RELACION',
            'apellido_paterno' => 'EXISTENTE',
            'genero' => 1,
        ]);
        $relacionAnterior = Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'url_respaldo' => 'tramitadores/historico/autorizacion-anterior.pdf',
            'fecha_registro' => '2026-01-01',
            'fecha_baja' => '2026-07-31',
            'estado' => 'INACTIVO',
        ]);

        $this->actingAs($usuarioEmpresa)
            ->get(route('tramitadores_show', $relacionAnterior))
            ->assertOk()
            ->assertSee('Solicitar nuevamente como tramitador');

        $respuesta = $this->actingAs($usuarioEmpresa)
            ->post(route('tramitadores_solicitar_nuevamente', $relacionAnterior), [
                'form_url_respaldo' => UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'),
            ]);

        $respuesta->assertRedirect(route('tramitadores_index'));
        $this->assertSame(1, Responsable::where('id_empresa', $empresa->id)->where('id_persona', $persona->id)->count());

        $relacionAnterior->refresh();
        $this->assertSame('PENDIENTE_VALIDACION', $relacionAnterior->estado);
        $this->assertNull($relacionAnterior->fecha_baja);
        $this->assertNotSame('tramitadores/historico/autorizacion-anterior.pdf', $relacionAnterior->url_respaldo);
        $this->assertSame(now()->format('Y-m-d H:i'), substr($relacionAnterior->fecha_registro, 0, 16));
        Storage::disk('local')->assertExists($relacionAnterior->url_respaldo);

        $this->actingAs($usuarioEmpresa)
            ->post(route('tramitadores_solicitar_nuevamente', $relacionAnterior), [
                'form_url_respaldo' => UploadedFile::fake()->create('carta-repetida.pdf', 100, 'application/pdf'),
            ])
            ->assertSessionHasErrors('form_url_respaldo');

        $this->assertSame(1, Responsable::where('id_empresa', $empresa->id)->where('id_persona', $persona->id)->count());
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_sin_permiso_de_tramitadores_no_se_puede_registrar(): void
    {
        [$usuarioEmpresa] = $this->crearEmpresaConPermisos(false);

        $this->actingAs($usuarioEmpresa)
            ->get(route('tramitadores_create'))
            ->assertForbidden();
    }

    public function test_una_empresa_no_puede_ver_la_carta_de_otra_empresa(): void
    {
        Storage::fake('local');
        [$usuarioEmpresa] = $this->crearEmpresaConPermisos();
        [, $otraEmpresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'privado@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '5544332',
            'nombres' => 'DOCUMENTO',
            'apellido_paterno' => 'PRIVADO',
            'genero' => 1,
        ]);
        Storage::disk('local')->put('tramitadores/otra/carta.pdf', 'contenido');
        $relacion = Responsable::create([
            'id_empresa' => $otraEmpresa->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'url_respaldo' => 'tramitadores/otra/carta.pdf',
            'fecha_registro' => '2026-08-02',
            'estado' => 'PENDIENTE_VALIDACION',
        ]);

        $this->actingAs($usuarioEmpresa)->get(route('tramitadores_show', $relacion))->assertForbidden();
        $this->actingAs($usuarioEmpresa)->get(route('tramitadores_carta', $relacion))->assertForbidden();
    }

    public function test_la_autorizacion_se_muestra_en_el_navegador_sin_descarga_automatica(): void
    {
        Storage::fake('local');
        [$usuarioEmpresa, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'visor.pdf@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '7788112',
            'nombres' => 'VISOR',
            'apellido_paterno' => 'PDF',
            'genero' => 1,
        ]);
        Storage::disk('local')->put('tramitadores/prueba/autorizacion.pdf', '%PDF-1.4');
        $relacion = Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'url_respaldo' => 'tramitadores/prueba/autorizacion.pdf',
            'fecha_registro' => '2026-08-02',
            'estado' => 'PENDIENTE_VALIDACION',
        ]);

        $this->actingAs($usuarioEmpresa)
            ->get(route('tramitadores_carta', $relacion))
            ->assertOk()
            ->assertHeader(
                'Content-Disposition',
                "inline; filename=\"autorizacion-tramitador-{$relacion->id}.pdf\""
            );
    }

    public function test_inso_valida_y_crea_la_cuenta_solo_la_primera_vez(): void
    {
        Storage::fake('local');
        $validador = $this->crearValidador();
        [$usuarioEmpresa, $empresa, $territorio] = $this->crearEmpresaConPermisos();

        $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_ci' => '6655443',
            'form_nombres' => 'Nuevo',
            'form_apellido_paterno' => 'Tramitador',
            'form_correo' => 'nuevo.tramitador@example.com',
            'form_id_territorio' => $territorio->id,
            'form_genero' => '1',
            'form_url_respaldo' => UploadedFile::fake()->create('respaldo.pdf', 100, 'application/pdf'),
        ])->assertRedirect(route('tramitadores_index'));

        $relacion = Responsable::where('id_empresa', $empresa->id)->firstOrFail();
        $this->assertDatabaseHas('notificaciones_tramites', [
            'id_usuario_destino' => $validador->id,
            'id_certificado' => null,
            'mensaje' => $empresa->razon_social . ' registró a NUEVO TRAMITADOR como tramitador.',
            'estado' => 'ACTIVO',
        ]);
        $this->actingAs($validador)
            ->getJson(route('notificaciones_tramites'))
            ->assertOk()
            ->assertJsonPath('notificaciones.0.url', route('tramitadores_index'));

        $this->actingAs($validador)->put(route('tramitadores_update', $relacion), [
            'form_estado' => 'ACTIVO',
        ])->assertRedirect(route('tramitadores_index'));

        $relacion->refresh();
        $usuarioTramitador = $relacion->persona->usuario;

        $this->assertSame('ACTIVO', $relacion->estado);
        $this->assertSame($validador->id, $relacion->id_usuario_validacion);
        $this->assertSame('nuevo.tramitador@example.com', $usuarioTramitador->email);
        $this->assertTrue(Hash::check('6655443', $usuarioTramitador->password));
        $this->assertTrue($usuarioTramitador->tieneRol('tramitador'));
        $otroUsuario = User::factory()->create(['estado' => 1]);
        $this->actingAs($otroUsuario);
        $relacion->update(['fecha_baja' => '2026-12-31']);

        $this->assertSame($otroUsuario->id, $relacion->fresh()->id_usuario_modificacion);
        $this->assertSame($validador->id, $relacion->fresh()->id_usuario_validacion);

        $permisoDashboard = Permiso::firstOrCreate(
            ['nombre' => 'dashboard.ver'],
            ['estado' => 1]
        );
        $usuarioTramitador->permisosDirectos()->syncWithoutDetaching([$permisoDashboard->id]);

        $this->actingAs($usuarioTramitador)
            ->get(route('admin_dashboard'))
            ->assertOk();
    }

    public function test_inso_conserva_las_credenciales_si_la_cuenta_ya_existe(): void
    {
        Storage::fake('local');
        $validador = $this->crearValidador();
        [$usuarioEmpresa, $empresa, $territorio] = $this->crearEmpresaConPermisos();
        $usuarioTramitador = User::factory()->create([
            'password' => 'clave-actual',
            'estado' => 1,
        ]);
        $persona = Persona::create([
            'id_usuario' => $usuarioTramitador->id,
            'correo' => $usuarioTramitador->email,
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '1234567',
            'nombres' => 'CUENTA',
            'apellido_paterno' => 'EXISTENTE',
            'genero' => 1,
        ]);

        $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_id_persona' => $persona->id,
            'form_url_respaldo' => UploadedFile::fake()->create('respaldo.pdf', 100, 'application/pdf'),
        ]);

        $relacion = Responsable::where('id_empresa', $empresa->id)->where('id_persona', $persona->id)->firstOrFail();
        $usuariosAntes = User::count();

        $this->actingAs($validador)->put(route('tramitadores_update', $relacion), [
            'form_estado' => 'ACTIVO',
        ])->assertRedirect(route('tramitadores_index'));

        $this->assertSame($usuariosAntes, User::count());
        $this->assertTrue(Hash::check('clave-actual', $usuarioTramitador->fresh()->password));
        $this->assertSame('ACTIVO', $relacion->fresh()->estado);
    }

    public function test_inso_puede_asignar_una_nueva_contrasena_al_habilitar_al_tramitador(): void
    {
        Storage::fake('local');
        $validador = $this->crearValidador();
        [$usuarioEmpresa, $empresa, $territorio] = $this->crearEmpresaConPermisos();
        $usuarioTramitador = User::factory()->create([
            'password' => 'clave-actual',
            'estado' => 1,
        ]);
        $persona = Persona::create([
            'id_usuario' => $usuarioTramitador->id,
            'correo' => $usuarioTramitador->email,
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '7654321',
            'nombres' => 'CAMBIO',
            'apellido_paterno' => 'CLAVE',
            'genero' => 1,
        ]);

        $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_id_persona' => $persona->id,
            'form_url_respaldo' => UploadedFile::fake()->create('respaldo.pdf', 100, 'application/pdf'),
        ]);

        $relacion = Responsable::where('id_empresa', $empresa->id)
            ->where('id_persona', $persona->id)
            ->firstOrFail();

        $this->actingAs($validador)->put(route('tramitadores_update', $relacion), [
            'form_estado' => 'ACTIVO',
            'form_cambiar_password' => '1',
            'password' => 'NuevaClaveSegura123',
            'password_confirmation' => 'NuevaClaveSegura123',
        ])->assertRedirect(route('tramitadores_index'));

        $this->assertTrue(Hash::check('NuevaClaveSegura123', $usuarioTramitador->fresh()->password));
        $this->assertSame('ACTIVO', $relacion->fresh()->estado);
    }

    public function test_validacion_diferencia_la_empresa_solicitante_de_las_empresas_ya_habilitadas(): void
    {
        $validador = $this->crearValidador();
        [, $empresaHabilitada, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        [, $empresaSolicitante] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'relaciones.tramitador@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '6543210',
            'nombres' => 'VARIAS',
            'apellido_paterno' => 'EMPRESAS',
            'genero' => 1,
        ]);

        Responsable::create([
            'id_empresa' => $empresaHabilitada->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'fecha_registro' => '2026-07-01',
            'estado' => 'ACTIVO',
        ]);
        $solicitud = Responsable::create([
            'id_empresa' => $empresaSolicitante->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'fecha_registro' => '2026-08-02',
            'estado' => 'PENDIENTE_VALIDACION',
        ]);

        $this->actingAs($validador)
            ->get(route('tramitadores_edit', $solicitud))
            ->assertOk()
            ->assertSee('Solicitud que está revisando')
            ->assertSee($empresaSolicitante->razon_social)
            ->assertSee('Empresas donde ya está habilitado')
            ->assertSee($empresaHabilitada->razon_social);
    }

    public function test_inso_puede_dar_de_baja_a_un_tramitador_activo_desde_la_tabla(): void
    {
        $validador = $this->crearValidador();
        [, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'baja.tramitador@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '1098765',
            'nombres' => 'BAJA',
            'apellido_paterno' => 'INSO',
            'genero' => 1,
        ]);
        $tramitador = Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'fecha_registro' => '2026-08-02',
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($validador)
            ->get(route('tramitadores_index'))
            ->assertOk()
            ->assertSee('Dar de baja');

        $this->actingAs($validador)
            ->post(route('tramitadores_baja', $tramitador))
            ->assertRedirect(route('tramitadores_index'));

        $tramitador->refresh();
        $this->assertSame('INACTIVO', $tramitador->estado);
        $this->assertSame(now()->toDateString(), $tramitador->fecha_baja);
        $this->assertSame($validador->id, $tramitador->id_usuario_baja);
    }

    public function test_empresa_queda_registrada_como_usuario_que_dio_de_baja_al_tramitador(): void
    {
        [$usuarioEmpresa, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'baja.empresa@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '2098765',
            'nombres' => 'BAJA',
            'apellido_paterno' => 'EMPRESA',
            'genero' => 1,
        ]);
        $tramitador = Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'fecha_registro' => '2026-08-02',
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($usuarioEmpresa)
            ->post(route('tramitadores_baja', $tramitador))
            ->assertRedirect(route('tramitadores_index'));

        $tramitador->refresh();
        $this->assertSame('INACTIVO', $tramitador->estado);
        $this->assertSame($usuarioEmpresa->id, $tramitador->id_usuario_baja);
    }

    private function crearEmpresaConPermisos(bool $puedeVer = true): array
    {
        $ambito = Ambito::create(['nombre' => 'Ámbito ' . uniqid(), 'estado' => 1]);
        $territorio = Territorio::create([
            'id_ambito' => $ambito->id,
            'nombre' => 'Territorio ' . uniqid(),
            'codigo' => uniqid('T-'),
            'estado' => 'ACTIVO',
        ]);
        $tipoEmpresa = TipoEmpresa::create(['descripcion' => 'Tipo de prueba', 'estado' => 'ACTIVO']);
        $usuario = User::factory()->create(['estado' => 1]);
        $personaEmpresa = Persona::create([
            'id_usuario' => $usuario->id,
            'correo' => $usuario->email,
            'nit' => uniqid('NIT-'),
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        $empresa = Empresa::create([
            'id_persona' => $personaEmpresa->id,
            'id_tipo_empresa' => $tipoEmpresa->id,
            'razon_social' => 'Empresa de prueba ' . uniqid(),
            'matricula' => uniqid('MAT-'),
            'estado' => 'ACTIVO',
        ]);

        $rolEmpresa = Role::firstOrCreate(
            ['slug' => 'solicitante'],
            ['name' => 'Solicitante', 'estado' => 1]
        );
        $rolTramitador = Role::firstOrCreate(
            ['slug' => 'tramitador'],
            ['name' => 'Tramitador', 'estado' => 1]
        );
        if ($puedeVer) {
            $permisoVer = Permiso::firstOrCreate(
                ['nombre' => 'tramitadores.ver'],
                ['estado' => 1]
            );
            $rolEmpresa->permisos()->syncWithoutDetaching([$permisoVer->id]);
        }

        $usuario->roles()->attach($rolEmpresa->id);

        return [$usuario, $empresa, $territorio, $rolTramitador];
    }

    private function crearValidador(): User
    {
        $usuario = User::factory()->create(['estado' => 1]);
        Funcionario::create([
            'id_usuario' => $usuario->id,
            'nombres' => 'FUNCIONARIO',
            'apellido_paterno' => 'VALIDADOR',
            'apellido_materno' => 'INSO',
            'carnet' => uniqid('CI-'),
            'genero' => 1,
        ]);
        $permiso = Permiso::firstOrCreate(
            ['nombre' => 'tramitadores.validar'],
            ['estado' => 1]
        );
        $permisoDashboard = Permiso::firstOrCreate(
            ['nombre' => 'dashboard.ver'],
            ['estado' => 1]
        );
        $usuario->permisosDirectos()->syncWithoutDetaching([$permiso->id, $permisoDashboard->id]);

        return $usuario;
    }
}

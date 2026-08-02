<?php

namespace Tests\Feature;

use App\Models\Ambito;
use App\Models\Empresa;
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
use Tests\TestCase;

class TramitadorEmpresaTest extends TestCase
{
    use RefreshDatabase;

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
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '7788990',
            'nombres' => 'PERSONA',
            'apellido_paterno' => 'EXISTENTE',
            'genero' => 1,
        ]);

        $usuariosAntes = User::count();
        $personasAntes = Persona::count();

        $respuesta = $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_ci' => '7788990',
            'form_fecha_registro' => '2026-08-02',
            'form_carta_autorizacion' => UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'),
        ]);

        $respuesta->assertRedirect(route('tramitadores_index'));
        $this->assertSame($usuariosAntes, User::count());
        $this->assertSame($personasAntes, Persona::count());

        $relacion = Responsable::where('id_empresa', $empresa->id)
            ->where('id_persona', $persona->id)
            ->firstOrFail();

        $this->assertSame('PENDIENTE_VALIDACION', $relacion->estado);
        $this->assertSame('2026-08-02', $relacion->fecha_registro);
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
            'form_fecha_registro' => '2026-08-02',
            'form_carta_autorizacion' => UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'),
        ]);

        $respuesta->assertRedirect(route('tramitadores_index'));
        $this->assertSame($usuariosAntes, User::count());

        $natural = Natural::where('ci', '9900112')->firstOrFail();
        $this->assertNull($natural->persona->id_usuario);
        $this->assertDatabaseHas('responsables', [
            'id_empresa' => $empresa->id,
            'id_persona' => $natural->id_persona,
            'estado' => 'PENDIENTE_VALIDACION',
        ]);
    }

    public function test_no_duplica_una_relacion_existente_ni_deja_un_archivo_huerfano(): void
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
        Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $persona->id,
            'id_rol' => $rolTramitador->id,
            'fecha_registro' => '2026-01-01',
            'estado' => 'INACTIVO',
        ]);

        $respuesta = $this->actingAs($usuarioEmpresa)
            ->from(route('tramitadores_create'))
            ->post(route('tramitadores_store'), [
                'form_ci' => '1122334',
                'form_fecha_registro' => '2026-08-02',
                'form_carta_autorizacion' => UploadedFile::fake()->create('carta.pdf', 100, 'application/pdf'),
            ]);

        $respuesta->assertRedirect(route('tramitadores_create'))->assertSessionHasErrors('form_ci');
        $this->assertSame(1, Responsable::where('id_empresa', $empresa->id)->where('id_persona', $persona->id)->count());
        $this->assertSame([], Storage::disk('local')->allFiles());
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
}

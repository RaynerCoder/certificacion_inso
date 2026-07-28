<?php

namespace Tests\Feature;

use App\Http\Middleware\VerificarPermiso;
use App\Models\Area;
use App\Models\Cargo;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsuarioCargoTest extends TestCase
{
    use RefreshDatabase;

    public function test_registra_y_asigna_un_cargo_nuevo_con_todos_sus_datos(): void
    {
        $administrador = User::factory()->create(['estado' => 1]);
        $area = Area::create([
            'nombre' => 'Area de pruebas',
            'descripcion' => 'Area usada para comprobar el registro.',
            'estado' => 1,
        ]);
        $rol = Role::create([
            'name' => 'Funcionario',
            'slug' => 'funcionario-pruebas',
            'estado' => 1,
        ]);

        $respuesta = $this
            ->withoutMiddleware(VerificarPermiso::class)
            ->actingAs($administrador)
            ->post(route('usuarios_store'), $this->datosUsuario($rol, [
                'nombre' => 'Responsable tecnico',
                'id_area' => $area->id,
                'descripcion' => 'Responsable de la revision tecnica.',
                'estado' => '1',
            ]));

        $respuesta->assertRedirect(route('usuarios_index'));

        $cargo = Cargo::where('nombre', 'Responsable tecnico')->firstOrFail();
        $usuario = User::where('email', 'funcionario.pruebas@example.com')->firstOrFail();

        $this->assertSame($area->id, $cargo->id_area);
        $this->assertSame('Responsable de la revision tecnica.', $cargo->descripcion);
        $this->assertSame(1, (int) $cargo->estado);
        $this->assertDatabaseHas('funcionarios_cargos', [
            'id_funcionario' => $usuario->funcionario->id,
            'id_cargo' => $cargo->id,
        ]);
    }

    public function test_rechaza_un_cargo_nuevo_sin_area(): void
    {
        $administrador = User::factory()->create(['estado' => 1]);
        $rol = Role::create([
            'name' => 'Funcionario',
            'slug' => 'funcionario-pruebas',
            'estado' => 1,
        ]);

        $respuesta = $this
            ->withoutMiddleware(VerificarPermiso::class)
            ->actingAs($administrador)
            ->from(route('usuarios_create'))
            ->post(route('usuarios_store'), $this->datosUsuario($rol, [
                'nombre' => 'Cargo sin area',
                'id_area' => '',
                'descripcion' => null,
                'estado' => '1',
            ]));

        $respuesta
            ->assertRedirect(route('usuarios_create'))
            ->assertSessionHasErrors('form_cargos_nuevos.0.id_area');

        $this->assertDatabaseMissing('cargos', ['nombre' => 'Cargo sin area']);
        $this->assertDatabaseMissing('users', ['email' => 'funcionario.pruebas@example.com']);
    }

    public function test_rechaza_el_nombre_de_un_cargo_ya_registrado(): void
    {
        $administrador = User::factory()->create(['estado' => 1]);
        $area = Area::create([
            'nombre' => 'Area de pruebas',
            'estado' => 1,
        ]);
        $rol = Role::create([
            'name' => 'Funcionario',
            'slug' => 'funcionario-pruebas',
            'estado' => 1,
        ]);
        Cargo::create([
            'nombre' => 'Cargo existente',
            'id_area' => $area->id,
            'estado' => 1,
        ]);

        $respuesta = $this
            ->withoutMiddleware(VerificarPermiso::class)
            ->actingAs($administrador)
            ->from(route('usuarios_create'))
            ->post(route('usuarios_store'), $this->datosUsuario($rol, [
                'nombre' => 'Cargo existente',
                'id_area' => $area->id,
                'descripcion' => 'No debe reemplazar el registro original.',
                'estado' => '1',
            ]));

        $respuesta
            ->assertRedirect(route('usuarios_create'))
            ->assertSessionHasErrors('form_cargos_nuevos.0.nombre');

        $this->assertSame(1, Cargo::where('nombre', 'Cargo existente')->count());
        $this->assertDatabaseMissing('users', ['email' => 'funcionario.pruebas@example.com']);
    }

    private function datosUsuario(Role $rol, array $cargoNuevo): array
    {
        return [
            'form_name' => 'Funcionario de pruebas',
            'form_email' => 'funcionario.pruebas@example.com',
            'form_password' => 'password123',
            'form_password_confirmation' => 'password123',
            'form_estado' => '1',
            'form_funcionario_nombres' => 'Nombre',
            'form_funcionario_apellido_paterno' => 'Apellido',
            'form_funcionario_apellido_materno' => null,
            'form_funcionario_carnet' => 'CI-PRUEBAS-001',
            'form_funcionario_telefono' => null,
            'form_funcionario_genero' => '1',
            'form_cargos' => [],
            'form_cargos_nuevos' => [$cargoNuevo],
            'form_roles' => [$rol->id],
            'form_permisos' => [],
        ];
    }
}

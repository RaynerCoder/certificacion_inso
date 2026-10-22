<?php

namespace Tests\Feature;

use App\Http\Controllers\SeguimientoController;
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
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class TramitadorEmpresaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Campo preparado por SQL manual: se agrega únicamente en SQLite para estas pruebas.
        \Illuminate\Support\Facades\Schema::table('responsables', function ($table) {
            $table->text('motivo_baja')->nullable();
        });
    }

    public function test_baja_rechaza_un_motivo_vacio_o_solo_espacios(): void
    {
        foreach (['', '   '] as $motivo) {
            try {
                app(\App\Services\GestionTramitadoresService::class)->darDeBaja(new Responsable(), new User(), $motivo);
                $this->fail('No debe admitir una baja sin motivo.');
            } catch (ValidationException $e) {
                $this->assertSame(['Indique el motivo de la baja.'], $e->errors()['motivo_baja']);
            }
        }
    }

    public function test_baja_permite_elegir_otro_tramitador_y_le_habilita_el_expediente_asignado(): void
    {
        [$representante, $empresa, $territorio, $rol] = $this->crearEmpresaConPermisos();
        $usuarios = [];
        $personas = [];
        $relaciones = [];
        foreach (['ANTERIOR', 'NUEVO'] as $index => $nombre) {
            $usuarios[$index] = User::factory()->create(['estado' => 1]);
            $usuarios[$index]->roles()->attach($rol->id);
            $personas[$index] = $this->crearPersonaNatural($territorio, 'BAJA-'.$index, $nombre, 'GESTOR', $usuarios[$index]);
            $relaciones[$index] = Responsable::create([
                'id_empresa' => $empresa->id, 'id_persona' => $personas[$index]->id, 'id_rol' => $rol->id, 'estado' => 'ACTIVO',
            ]);
        }
        $tipo = \App\Models\TipoCertificado::create(['nombre' => 'Transferencia elegida', 'estado' => 'ACTIVO']);
        $certificado = new \App\Models\Certificado([
            'id_tipo_certificado' => $tipo->id, 'id_persona_beneficiario' => $empresa->id_persona,
            'id_persona_tramitador' => $personas[0]->id, 'estado' => 'OBSERVADO',
        ]);
        $certificado->codigo = 'BAJA-ELEGIDA';
        $certificado->id_usuario_registro = $usuarios[0]->id;
        $certificado->save();
        \App\Models\Seguimiento::create([
            'id_certificado' => $certificado->id, 'id_usuario_origen' => $representante->id,
            'id_usuario_siguiente' => $usuarios[0]->id, 'fecha_inicio' => now(), 'estado' => 'ACTIVO',
        ]);
        $servicio = app(\App\Services\GestionTramitadoresService::class);
        $this->assertFalse($servicio->destinatariosBaja($relaciones[0])->contains('id', $usuarios[0]->id));
        $this->assertTrue($servicio->destinatariosBaja($relaciones[0])->contains('id', $usuarios[1]->id));
        $this->assertFalse($servicio->usuarioPuedeConsultarTramite($usuarios[1], $certificado));
        $transferidos = $servicio->darDeBaja($relaciones[0], $representante, 'Cambio de persona autorizada.', $usuarios[1]->id);
        $this->assertSame(1, $transferidos);
        $ultimo = $certificado->seguimientos()->latest('id')->firstOrFail();
        $this->assertSame($usuarios[1]->id, $ultimo->id_usuario_siguiente);
        $this->assertTrue($servicio->usuarioPuedeConsultarTramite($usuarios[1], $certificado->fresh()));
        $this->assertTrue($servicio->usuarioPuedeResponderCorreccion($usuarios[1], $certificado->fresh()));
        $this->assertSame($personas[1]->id, $servicio->gestorActual($certificado->fresh())['persona']->id);
        $this->assertDatabaseHas('notificaciones_tramites', ['id_certificado' => $certificado->id, 'id_usuario_destino' => $usuarios[1]->id, 'titulo' => 'Tramite pendiente de correccion']);
        $this->actingAs($usuarios[1]);
        $tabla = app(\App\Livewire\Datatables\SeguimientoTable::class);
        $tabla->bandeja = 'enviadas';
        $this->assertTrue($tabla->builder()->where('id_certificado', $certificado->id)->exists());
    }

    public function test_baja_rechaza_destinatarios_inactivos_pendientes_de_validacion_o_de_otra_empresa(): void
    {
        [$representante, $empresa, $territorio, $rol] = $this->crearEmpresaConPermisos();
        [, $otraEmpresa] = $this->crearEmpresaConPermisos();
        $usuarios = [];
        $relaciones = [];
        foreach (['ACTIVO', 'PENDIENTE_VALIDACION', 'INACTIVO', 'OTRA_EMPRESA'] as $index => $estado) {
            $usuarios[$index] = User::factory()->create(['estado' => 1]);
            $persona = $this->crearPersonaNatural($territorio, 'NO-BAJA-'.$index, 'PERSONA'.$index, 'PRUEBA', $usuarios[$index]);
            $relaciones[$index] = Responsable::create([
                'id_empresa' => $index === 3 ? $otraEmpresa->id : $empresa->id,
                'id_persona' => $persona->id, 'id_rol' => $rol->id,
                'estado' => $index === 3 ? 'ACTIVO' : $estado,
            ]);
        }
        $tipo = \App\Models\TipoCertificado::create(['nombre' => 'Transferencia bloqueada', 'estado' => 'ACTIVO']);
        $certificado = new \App\Models\Certificado([
            'id_tipo_certificado' => $tipo->id, 'id_persona_beneficiario' => $empresa->id_persona,
            'id_persona_tramitador' => $relaciones[0]->id_persona, 'estado' => 'OBSERVADO',
        ]);
        $certificado->codigo = 'BAJA-BLOQUEADA';
        $certificado->save();
        $seguimiento = \App\Models\Seguimiento::create([
            'id_certificado' => $certificado->id, 'id_usuario_origen' => $representante->id,
            'id_usuario_siguiente' => $usuarios[0]->id, 'estado' => 'ACTIVO',
        ]);
        foreach ($usuarios as $usuario) {
            try {
                app(\App\Services\GestionTramitadoresService::class)->darDeBaja($relaciones[0]->fresh(), $representante, 'Motivo.', $usuario->id);
                $this->fail('No debe permitir este destinatario.');
            } catch (ValidationException $e) {
                $this->assertArrayHasKey('id_usuario_destinatario_baja', $e->errors());
                $this->assertSame('ACTIVO', $relaciones[0]->fresh()->estado);
                $this->assertNull($seguimiento->fresh()->fecha_derivacion);
            }
        }
    }

    public function test_gestor_externo_cambia_solo_al_recibir_el_tramite_y_se_conserva_al_volver_al_tecnico(): void
    {
        [$cuentaEmpresa, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $rolRepresentante = Role::where('slug', 'solicitante')->firstOrFail();
        $usuarios = [];
        $personas = [];
        $vinculos = [];
        foreach (['INICIAL', 'REPRESENTANTE', 'NUEVO'] as $index => $nombre) {
            $usuarios[$index] = User::factory()->create(['estado' => 1]);
            $personas[$index] = $this->crearPersonaNatural($territorio, 'GESTOR-'.$index, $nombre, 'PRUEBA', $usuarios[$index]);
            $vinculos[$index] = Responsable::create([
                'id_empresa' => $empresa->id, 'id_persona' => $personas[$index]->id,
                'id_rol' => $index === 1 ? $rolRepresentante->id : $rolTramitador->id,
                'estado' => 'ACTIVO',
            ]);
        }
        $tipo = \App\Models\TipoCertificado::create(['nombre' => 'Gestión visual', 'estado' => 'ACTIVO']);
        $certificado = new \App\Models\Certificado([
            'id_tipo_certificado' => $tipo->id, 'id_persona_beneficiario' => $empresa->id_persona,
            'id_persona_tramitador' => $personas[0]->id, 'estado' => 'OBSERVADO',
        ]);
        $certificado->codigo = 'GESTOR-1';
        $certificado->id_usuario_registro = $usuarios[0]->id;
        $certificado->save();
        $servicio = app(\App\Services\GestionTramitadoresService::class);
        // El nuevo tramitador ya está registrado, pero no recibió este expediente.
        $this->assertSame($personas[0]->id, $servicio->gestorActual($certificado->fresh())['persona']->id);
        $tecnico = User::factory()->create(['estado' => 1]);
        $movimiento = \App\Models\Seguimiento::create([
            'id_certificado' => $certificado->id, 'id_usuario_origen' => $tecnico->id,
            'id_usuario_siguiente' => $usuarios[1]->id, 'estado' => 'ACTIVO',
        ]);
        $vinculos[0]->update(['estado' => 'INACTIVO', 'fecha_baja' => now()]);
        $gestor = $servicio->gestorActual($certificado->fresh());
        $this->assertSame($personas[1]->id, $gestor['persona']->id);
        $this->assertSame('Representante legal', $gestor['tipo']);
        $retorno = \App\Models\Seguimiento::create([
            'id_certificado' => $certificado->id, 'id_seguimiento_padre' => $movimiento->id,
            'id_usuario_origen' => $usuarios[1]->id, 'id_usuario_siguiente' => $tecnico->id, 'estado' => 'ACTIVO',
        ]);
        $this->assertSame($personas[1]->id, $servicio->gestorActual($certificado->fresh())['persona']->id);
        \App\Models\Seguimiento::create([
            'id_certificado' => $certificado->id, 'id_seguimiento_padre' => $retorno->id,
            'id_usuario_origen' => $tecnico->id, 'id_usuario_siguiente' => $usuarios[2]->id, 'estado' => 'ACTIVO',
        ]);
        $gestor = $servicio->gestorActual($certificado->fresh());
        $this->assertSame($personas[2]->id, $gestor['persona']->id);
        $this->assertSame('Tramitador', $gestor['tipo']);
        $this->assertSame($personas[0]->id, $certificado->fresh()->id_persona_tramitador);
    }

    public function test_correccion_vuelve_al_ultimo_revisor_aunque_haya_transferencias_entre_solicitantes(): void
    {
        [$representante, $empresa, $territorio] = $this->crearEmpresaConPermisos();
        $otroTramitador = User::factory()->create(['estado' => 1]);
        $persona = $this->crearPersonaNatural($territorio, '9876001', 'NUEVO', 'TRAMITADOR', $otroTramitador);
        $area = \App\Models\Area::create(['nombre' => 'Área técnica', 'estado' => 1]);
        $revisores = [];
        foreach (['ANTERIOR', 'ULTIMO'] as $nombre) {
            $usuario = User::factory()->create(['estado' => 1]);
            $funcionario = Funcionario::create([
                'id_usuario' => $usuario->id, 'nombres' => $nombre, 'apellido_paterno' => 'TECNICO',
                'ci' => 'REV-'.$usuario->id, 'genero' => 1,
            ]);
            $cargo = \App\Models\Cargo::create(['id_area' => $area->id, 'nombre' => 'Revisor '.$nombre, 'estado' => 1]);
            $funcionario->cargos()->attach($cargo->id);
            $revisores[] = $usuario;
        }
        $tipo = \App\Models\TipoCertificado::create(['nombre' => 'Prueba de retorno', 'estado' => 'ACTIVO']);
        $certificado = new \App\Models\Certificado([
            'id_tipo_certificado' => $tipo->id, 'id_persona_beneficiario' => $empresa->id_persona,
            'id_persona_tramitador' => $persona->id, 'estado' => 'OBSERVADO',
        ]);
        $certificado->codigo = 'RETORNO-1';
        $certificado->save();
        $requisito = \App\Models\Requisito::create(['descripcion' => 'Documento observado', 'estado' => 'ACTIVO']);
        $asignacion = \App\Models\RequisitoCertificado::create([
            'id_certificado' => $certificado->id, 'id_requisito' => $requisito->id, 'cumple' => 'NO', 'estado' => 'OBSERVADO',
        ]);
        $notificacion = \App\Models\Seguimiento::create([
            'id_certificado' => $certificado->id, 'id_usuario_origen' => $revisores[0]->id,
            'id_usuario_siguiente' => $otroTramitador->id, 'estado' => 'ACTIVO',
        ]);
        $transferencia = \App\Models\Seguimiento::create([
            'id_seguimiento_padre' => $notificacion->id, 'id_certificado' => $certificado->id,
            'id_usuario_origen' => $representante->id, 'id_usuario_siguiente' => $representante->id, 'estado' => 'ACTIVO',
        ]);
        $metodo = new \ReflectionMethod(SeguimientoController::class, 'revisorDestinoCorreccion');
        $controller = app(SeguimientoController::class);
        // Los registros antiguos sin revisiones también deben saltar la transferencia administrativa.
        $this->assertSame($revisores[0]->id, $metodo->invoke($controller, $certificado, $transferencia, collect([$asignacion]))->id);
        $transferencia->id_usuario_origen = $revisores[1]->id;
        $transferencia->descripcion_final = 'Correccion transferida al beneficiario por baja del tramitador.';
        $this->assertSame($revisores[0]->id, $metodo->invoke($controller, $certificado, $transferencia, collect([$asignacion]))->id);
        $transferencia->id_usuario_origen = $representante->id;
        foreach ($revisores as $revisor) {
            \App\Models\RevisionRequisito::create([
                'id_requisito_certificado' => $asignacion->id, 'id_usuario_revisor' => $revisor->id,
                'resultado_cumple' => 'NO', 'estado' => 'ACTIVO',
            ]);
        }
        $this->assertSame($revisores[1]->id, $metodo->invoke($controller, $certificado, $transferencia, collect([$asignacion]))->id);
        // Otro cambio de persona autorizada no cambia al técnico de retorno.
        $transferencia->id_usuario_origen = $otroTramitador->id;
        $transferencia->id_usuario_siguiente = $otroTramitador->id;
        $this->assertSame($revisores[1]->id, $metodo->invoke($controller, $certificado, $transferencia, collect([$asignacion]))->id);
        // No se redirige a otra persona ni a sí mismo cuando el último revisor está inactivo.
        $revisores[1]->update(['estado' => 0]);
        $this->expectException(ValidationException::class);
        $metodo->invoke($controller, $certificado, $transferencia, collect([$asignacion]));
    }

    public function test_baja_solo_transfiere_correcciones_de_la_empresa_seleccionada(): void
    {
        [$usuarioA, $empresaA, $territorio, $rol] = $this->crearEmpresaConPermisos();
        [$usuarioB, $empresaB] = $this->crearEmpresaConPermisos();
        $usuarioTramitador = User::factory()->create(['estado' => 1]);
        $persona = $this->crearPersonaNatural($territorio, '8899771', 'JUAN', 'COMPARTIDO', $usuarioTramitador);
        $tipo = \App\Models\TipoCertificado::create(['nombre' => 'Prueba de aislamiento', 'costo' => 0, 'estado' => 'ACTIVO']);
        $relaciones = [];
        $pendientes = [];
        $avisos = [];
        foreach ([$empresaA, $empresaB] as $empresa) {
            $relaciones[] = Responsable::create([
                'id_empresa' => $empresa->id, 'id_persona' => $persona->id,
                'id_rol' => $rol->id, 'estado' => 'ACTIVO',
            ]);
            $certificado = new \App\Models\Certificado;
            $certificado->forceFill([
                'id_tipo_certificado' => $tipo->id,
                'id_persona_beneficiario' => $empresa->id_persona,
                'id_persona_tramitador' => $persona->id,
                'codigo' => 'AISLAMIENTO-'.$empresa->id,
                'estado' => 'OBSERVADO',
            ])->save();
            $pendientes[] = \App\Models\Seguimiento::create([
                'id_certificado' => $certificado->id, 'id_usuario_origen' => $usuarioA->id,
                'id_usuario_siguiente' => $usuarioTramitador->id,
                'fecha_inicio' => now(), 'estado' => 'ACTIVO',
            ]);
            $avisos[] = \App\Models\NotificacionTramite::create([
                'id_certificado' => $certificado->id, 'id_usuario_destino' => $usuarioTramitador->id,
                'titulo' => 'Corrección pendiente', 'estado' => 'ACTIVO',
            ]);
        }
        // Una empresa ajena sin cuenta activa tampoco debe bloquear esta baja.
        $usuarioB->update(['estado' => 0]);
        $transferidos = app(\App\Services\GestionTramitadoresService::class)->darDeBaja($relaciones[0], $usuarioA, '  Revocación de la autorización por la empresa.  ');
        $this->assertSame(1, $transferidos);
        $this->assertNotNull($pendientes[0]->fresh()->fecha_derivacion);
        $this->assertNull($pendientes[1]->fresh()->fecha_derivacion);
        $this->assertSame($usuarioTramitador->id, $pendientes[1]->fresh()->id_usuario_siguiente);
        $this->assertDatabaseHas('seguimientos', [
            'id_seguimiento_padre' => $pendientes[0]->id,
            'id_usuario_siguiente' => $usuarioA->id,
        ]);
        $this->assertDatabaseMissing('seguimientos', ['id_seguimiento_padre' => $pendientes[1]->id]);
        $this->assertSame('INACTIVO', $relaciones[0]->fresh()->estado);
        $this->assertSame('Revocación de la autorización por la empresa.', $relaciones[0]->fresh()->motivo_baja);
        $this->assertNull($relaciones[1]->fresh()->motivo_baja);
        $this->assertSame('ACTIVO', $relaciones[1]->fresh()->estado);
        $this->assertSame('INACTIVO', $avisos[0]->fresh()->estado);
        $this->assertSame('ACTIVO', $avisos[1]->fresh()->estado);
        $this->assertSame(1, (int) $usuarioTramitador->fresh()->estado);
    }

    public function test_representante_legal_no_desaparece_al_registrar_otros_responsables(): void
    {
        [, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $personaRepresentante = $this->crearPersonaNatural($territorio, '8800111', 'MARIO', 'REPRESENTANTE');
        $personaTramitador = $this->crearPersonaNatural($territorio, '8800222', 'JUAN', 'TRAMITADOR');
        $idRolRepresentante = Role::where('slug', 'solicitante')->value('id');
        $representante = Responsable::create([
            'id_empresa' => $empresa->id, 'id_persona' => $personaRepresentante->id,
            'id_rol' => $idRolRepresentante, 'estado' => 'ACTIVO',
        ]);
        Responsable::create([
            'id_empresa' => $empresa->id, 'id_persona' => $personaTramitador->id,
            'id_rol' => $rolTramitador->id, 'estado' => 'ACTIVO',
        ]);
        Responsable::create([
            'id_empresa' => $empresa->id, 'id_persona' => $personaTramitador->id,
            'id_rol' => $idRolRepresentante, 'estado' => 'INACTIVO',
        ]);
        $this->assertSame($representante->id, $empresa->fresh()->representanteLegalActivo?->id);
        $this->assertSame($representante->id, Empresa::with('representanteLegalActivo')->findOrFail($empresa->id)->representanteLegalActivo?->id);
    }

    public function test_correccion_prioriza_al_iniciador_y_usa_representante_si_esta_inactivo(): void
    {
        [, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $representante = User::factory()->create(['estado' => 1]);
        $tramitador = User::factory()->create(['estado' => 1]);
        $personaRepresentante = $this->crearPersonaNatural($territorio, '7711001', 'MARIO', 'REPRESENTANTE', $representante);
        $personaTramitador = $this->crearPersonaNatural($territorio, '7711002', 'JUAN', 'TRAMITADOR', $tramitador);
        Responsable::create([
            'id_empresa' => $empresa->id, 'id_persona' => $personaRepresentante->id,
            'id_rol' => Role::where('slug', 'solicitante')->value('id'), 'estado' => 'ACTIVO',
        ]);
        $vinculo = Responsable::create([
            'id_empresa' => $empresa->id, 'id_persona' => $personaTramitador->id,
            'id_rol' => $rolTramitador->id, 'estado' => 'ACTIVO',
        ]);
        $certificado = new \App\Models\Certificado([
            'id_persona_beneficiario' => $empresa->id_persona,
            'id_persona_tramitador' => $personaTramitador->id,
        ]);
        $certificado->id_usuario_registro = $tramitador->id;
        $servicio = app(\App\Services\GestionTramitadoresService::class);
        $opciones = $servicio->destinatariosCorreccion($certificado);
        $this->assertSame($tramitador->id, $opciones->first()['id']);
        $this->assertSame('Tramitador (inició el trámite)', $opciones->first()['etiqueta']);
        $this->assertSame($tramitador->id, $servicio->idDestinatarioPredeterminado($certificado));
        $vinculo->update(['estado' => 'INACTIVO']);
        $certificado->unsetRelation('beneficiario');
        $this->assertSame($representante->id, $servicio->idDestinatarioPredeterminado($certificado));
        $this->assertFalse($servicio->destinatariosCorreccion($certificado)->contains('id', $tramitador->id));
    }

    public function test_registro_informa_campos_faltantes_y_exige_pdf_con_mensajes_claros(): void
    {
        [$usuarioEmpresa] = $this->crearEmpresaConPermisos();

        $respuesta = $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_ci' => '9988776655',
        ]);

        $respuesta->assertSessionHasErrors([
            'form_url_respaldo' => 'Debe adjuntar el documento de respaldo o autorización en PDF.',
            'form_nombres' => 'Debe completar el campo nombres.',
            'form_apellido_paterno' => 'Debe completar el campo apellido paterno.',
            'form_correo' => 'Debe completar el campo correo.',
            'form_id_territorio' => 'Debe completar el campo territorio.',
            'form_genero' => 'Debe completar el campo género.',
            'form_fecha_nacimiento' => 'Debe completar el campo fecha de nacimiento.',
        ]);
    }

    public function test_registro_rechaza_respaldo_que_no_es_pdf(): void
    {
        [$usuarioEmpresa] = $this->crearEmpresaConPermisos();

        $this->actingAs($usuarioEmpresa)->post(route('tramitadores_store'), [
            'form_ci' => '9988776655',
            'form_url_respaldo' => UploadedFile::fake()->create('respaldo.txt', 10, 'text/plain'),
        ])->assertSessionHasErrors([
            'form_url_respaldo' => 'El documento de respaldo o autorización debe ser un archivo PDF.',
        ]);
    }

    public function test_registro_interno_separa_empresa_beneficiaria_de_sus_personas_tramitadoras(): void
    {
        $funcionario = User::factory()->create(['estado' => 1]);
        $rolFuncionario = Role::firstOrCreate(
            ['slug' => 'funcionario'],
            ['name' => 'Funcionario', 'estado' => 1]
        );
        $funcionario->roles()->attach($rolFuncionario->id);

        $ambito = Ambito::create(['nombre' => 'Ámbito interno', 'estado' => 1]);
        $territorio = Territorio::create([
            'id_ambito' => $ambito->id,
            'nombre' => 'Territorio interno',
            'codigo' => 'T-INTERNO',
            'estado' => 'ACTIVO',
        ]);
        $tipoEmpresa = TipoEmpresa::create(['descripcion' => 'Empresa interna', 'estado' => 'ACTIVO']);
        $personaEmpresa = Persona::create([
            'nit' => 'NIT-EMPRESA-INTERNA',
            'correo' => 'empresa.beneficiaria@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        $empresa = Empresa::create([
            'id_persona' => $personaEmpresa->id,
            'id_tipo_empresa' => $tipoEmpresa->id,
            'razon_social' => 'EMPRESA BENEFICIARIA',
            'matricula' => 'MAT-EMPRESA-INTERNA',
            'estado' => 'ACTIVO',
        ]);

        $representante = $this->crearPersonaNatural($territorio, '10001', 'REPRESENTANTE', 'LEGAL');
        $tramitador = $this->crearPersonaNatural($territorio, '10002', 'TRAMITADOR', 'AUTORIZADO');
        $personaSinEmpresa = $this->crearPersonaNatural($territorio, '10003', 'BENEFICIARIO', 'NATURAL');
        $rolRepresentante = Role::firstOrCreate(
            ['slug' => 'solicitante'],
            ['name' => 'Solicitante', 'estado' => 1]
        );
        $rolTramitador = Role::firstOrCreate(
            ['slug' => 'tramitador'],
            ['name' => 'Tramitador', 'estado' => 1]
        );

        Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $representante->id,
            'id_rol' => $rolRepresentante->id,
            'fecha_registro' => now()->toDateString(),
            'estado' => 'ACTIVO',
        ]);
        Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $tramitador->id,
            'id_rol' => $rolTramitador->id,
            'fecha_registro' => now()->toDateString(),
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($funcionario);
        $controlador = app(SeguimientoController::class);
        $metodo = new \ReflectionMethod($controlador, 'datosFormularioTramite');
        $datos = $metodo->invoke($controlador);

        $idsBeneficiarios = collect($datos['personas'])->pluck('id');
        $this->assertTrue($idsBeneficiarios->contains($personaEmpresa->id));
        $this->assertTrue($idsBeneficiarios->contains($personaSinEmpresa->id));
        $this->assertFalse($idsBeneficiarios->contains($representante->id));
        $this->assertFalse($idsBeneficiarios->contains($tramitador->id));

        $opcionesTramitador = collect($datos['tramitadoresPorBeneficiario'][$personaEmpresa->id]);
        $this->assertSame(
            'Representante legal',
            $opcionesTramitador->firstWhere('id', $representante->id)['detalle'] ?? null
        );
        $this->assertSame(
            'Tramitador autorizado',
            $opcionesTramitador->firstWhere('id', $tramitador->id)['detalle'] ?? null
        );
        $this->assertFalse($opcionesTramitador->contains('id', $personaEmpresa->id));
    }

    public function test_registro_interno_no_acepta_una_empresa_como_su_propio_tramitador(): void
    {
        $funcionario = User::factory()->create(['estado' => 1]);
        $rolFuncionario = Role::firstOrCreate(
            ['slug' => 'funcionario'],
            ['name' => 'Funcionario', 'estado' => 1]
        );
        $funcionario->roles()->attach($rolFuncionario->id);
        [, $empresa, $territorio] = $this->crearEmpresaConPermisos();
        $representante = $this->crearPersonaNatural($territorio, '10004', 'OTRO', 'REPRESENTANTE');
        $rolRepresentante = Role::where('slug', 'solicitante')->firstOrFail();
        Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $representante->id,
            'id_rol' => $rolRepresentante->id,
            'fecha_registro' => now()->toDateString(),
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($funcionario);
        $controlador = app(SeguimientoController::class);
        $metodo = new \ReflectionMethod($controlador, 'resolverTramitadorDelBeneficiario');

        $this->expectException(ValidationException::class);
        $metodo->invoke(
            $controlador,
            $funcionario,
            $empresa->id_persona,
            $empresa->id_persona
        );
    }

    public function test_empresa_sin_representante_legal_no_puede_iniciar_tramites(): void
    {
        $funcionario = User::factory()->create(['estado' => 1]);
        $rolFuncionario = Role::firstOrCreate(
            ['slug' => 'funcionario'],
            ['name' => 'Funcionario', 'estado' => 1]
        );
        $funcionario->roles()->attach($rolFuncionario->id);
        [, $empresa, $territorio, $rolTramitador] = $this->crearEmpresaConPermisos();
        $tramitador = $this->crearPersonaNatural($territorio, '10005', 'SOLO', 'TRAMITADOR');
        Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $tramitador->id,
            'id_rol' => $rolTramitador->id,
            'fecha_registro' => now()->toDateString(),
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($funcionario);
        $controlador = app(SeguimientoController::class);
        $datosFormulario = new \ReflectionMethod($controlador, 'datosFormularioTramite');
        $datos = $datosFormulario->invoke($controlador);

        $this->assertFalse(collect($datos['personas'])->contains('id', $empresa->id_persona));

        $resolverTramitador = new \ReflectionMethod($controlador, 'resolverTramitadorDelBeneficiario');
        $this->expectException(ValidationException::class);
        $resolverTramitador->invoke(
            $controlador,
            $funcionario,
            $empresa->id_persona,
            $tramitador->id
        );
    }

    public function test_representante_externo_tramita_para_su_empresa_con_la_relacion_visible(): void
    {
        $usuarioRepresentante = User::factory()->create(['estado' => 1]);
        [$empresa, $territorio] = $this->crearEmpresaBasica('EMPRESA EXTERNA');
        $personaRepresentante = $this->crearPersonaNatural(
            $territorio,
            '10006',
            'REPRESENTANTE',
            'EXTERNO',
            $usuarioRepresentante
        );
        $rolRepresentante = Role::firstOrCreate(
            ['slug' => 'solicitante'],
            ['name' => 'Solicitante', 'estado' => 1]
        );
        Responsable::create([
            'id_empresa' => $empresa->id,
            'id_persona' => $personaRepresentante->id,
            'id_rol' => $rolRepresentante->id,
            'fecha_registro' => now()->toDateString(),
            'estado' => 'ACTIVO',
        ]);

        $this->actingAs($usuarioRepresentante);
        $controlador = app(SeguimientoController::class);
        $datosFormulario = new \ReflectionMethod($controlador, 'datosFormularioTramite');
        $datos = $datosFormulario->invoke($controlador);
        $opcion = collect($datos['tramitadoresPorBeneficiario'][$empresa->id_persona])->first();

        $this->assertSame($empresa->id_persona, $datos['beneficiarioAutomatico']['id']);
        $this->assertTrue($datos['beneficiarioBloqueado']);
        $this->assertTrue($datos['tramitadorBloqueado']);
        $this->assertSame($personaRepresentante->id, $opcion['id']);
        $this->assertSame('Representante legal', $opcion['detalle']);

        $resolverTramitador = new \ReflectionMethod($controlador, 'resolverTramitadorDelBeneficiario');
        $idResuelto = $resolverTramitador->invoke(
            $controlador,
            $usuarioRepresentante,
            $empresa->id_persona,
            $empresa->id_persona
        );

        $this->assertSame($personaRepresentante->id, $idResuelto);
    }

    public function test_persona_natural_externa_sin_empresa_es_beneficiaria_y_tramitadora(): void
    {
        $usuario = User::factory()->create(['estado' => 1]);
        $ambito = Ambito::create(['nombre' => 'Ámbito natural', 'estado' => 1]);
        $territorio = Territorio::create([
            'id_ambito' => $ambito->id,
            'nombre' => 'Territorio natural',
            'codigo' => 'T-NATURAL',
            'estado' => 'ACTIVO',
        ]);
        $persona = $this->crearPersonaNatural(
            $territorio,
            '10007',
            'PERSONA',
            'INDEPENDIENTE',
            $usuario
        );

        $this->actingAs($usuario);
        $controlador = app(SeguimientoController::class);
        $datosFormulario = new \ReflectionMethod($controlador, 'datosFormularioTramite');
        $datos = $datosFormulario->invoke($controlador);
        $opcion = collect($datos['tramitadoresPorBeneficiario'][$persona->id])->first();

        $this->assertSame($persona->id, $datos['beneficiarioAutomatico']['id']);
        $this->assertTrue($datos['beneficiarioBloqueado']);
        $this->assertTrue($datos['tramitadorBloqueado']);
        $this->assertSame($persona->id, $opcion['id']);
        $this->assertSame('Beneficiario y tramitador', $opcion['detalle']);

        $resolverTramitador = new \ReflectionMethod($controlador, 'resolverTramitadorDelBeneficiario');
        $this->assertSame(
            $persona->id,
            $resolverTramitador->invoke($controlador, $usuario, $persona->id, null)
        );
    }

    public function test_la_busqueda_por_ci_entrega_los_datos_de_la_persona_existente(): void
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
            ->getJson(route('tramitadores_buscar_persona', ['form_ci' => '4455667']))
            ->assertOk()
            ->assertJsonPath('encontrado', true)
            ->assertJsonPath('persona.nombres', 'PERSONA')
            ->assertJsonPath('persona.fecha_nacimiento', '1992-04-18');
    }

    public function test_el_formulario_no_expone_el_listado_de_personas_registradas(): void
    {
        [$usuarioEmpresa, , $territorio] = $this->crearEmpresaConPermisos();
        $persona = Persona::create([
            'correo' => 'dato.no.expuesto@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => '1122334',
            'nombres' => 'PERSONA NO EXPUESTA',
            'apellido_paterno' => 'PRIVADA',
            'genero' => 1,
        ]);

        $this->actingAs($usuarioEmpresa)
            ->get(route('tramitadores_create'))
            ->assertOk()
            ->assertSee('Buscar persona por CI')
            ->assertDontSee('form_id_persona')
            ->assertDontSee('PERSONA NO EXPUESTA')
            ->assertDontSee('dato.no.expuesto@example.com');
    }

    public function test_la_busqueda_indica_cuando_el_ci_no_esta_registrado(): void
    {
        [$usuarioEmpresa] = $this->crearEmpresaConPermisos();

        $this->actingAs($usuarioEmpresa)
            ->getJson(route('tramitadores_buscar_persona', ['form_ci' => 'CI-NUEVO']))
            ->assertOk()
            ->assertExactJson(['encontrado' => false]);
    }

    public function test_la_busqueda_solicita_complemento_si_el_ci_tiene_mas_de_un_registro(): void
    {
        [$usuarioEmpresa, , $territorio] = $this->crearEmpresaConPermisos();

        foreach (['1A', '2B'] as $complemento) {
            $persona = Persona::create([
                'correo' => strtolower($complemento).'@example.com',
                'id_territorio' => $territorio->id,
                'estado' => 'ACTIVO',
            ]);
            Natural::create([
                'id_persona' => $persona->id,
                'ci' => '5566778',
                'complemento' => $complemento,
                'nombres' => 'PERSONA '.$complemento,
                'apellido_paterno' => 'DUPLICADA',
                'genero' => 1,
            ]);
        }

        $this->actingAs($usuarioEmpresa)
            ->getJson(route('tramitadores_buscar_persona', ['form_ci' => '5566778']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('form_complemento');

        $this->actingAs($usuarioEmpresa)
            ->getJson(route('tramitadores_buscar_persona', ['form_ci' => '5566778', 'form_complemento' => '2b']))
            ->assertOk()
            ->assertJsonPath('persona.nombres', 'PERSONA 2B')
            ->assertJsonPath('persona.complemento', '2B');
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
            'form_ci' => '7788990',
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
            'form_fecha_nacimiento' => '1990-01-15',
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
        $respaldo = Responsable::where('id_empresa', $empresa->id)->where('id_persona', $natural->id_persona)->firstOrFail()->url_respaldo;
        $this->assertStringStartsWith("tramitadores/empresas/{$empresa->id}/", $respaldo);
        Storage::disk('local')->assertExists($respaldo);
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
        $this->assertStringStartsWith("tramitadores/empresas/{$empresa->id}/", $relacionAnterior->url_respaldo);
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
                "inline; filename=\"{$relacion->id}-responsable-respaldo.pdf\""
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
            'form_fecha_nacimiento' => '1990-01-15',
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
        $this->assertSame('nuevo_tramitador', $usuarioTramitador->name);
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
            'form_ci' => '1234567',
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
            'form_ci' => '7654321',
            'form_url_respaldo' => UploadedFile::fake()->create('respaldo.pdf', 100, 'application/pdf'),
        ]);

        $relacion = Responsable::where('id_empresa', $empresa->id)
            ->where('id_persona', $persona->id)
            ->firstOrFail();

        $this->actingAs($validador)->put(route('tramitadores_update', $relacion), [
            'form_estado' => 'ACTIVO',
            'form_cambiar_password' => '1',
            'form_password' => 'NuevaClaveSegura123',
            'form_password_confirmation' => 'NuevaClaveSegura123',
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
            ->post(route('tramitadores_baja', $tramitador), ['motivo_baja' => 'Baja registrada por INSO.'])
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
            ->post(route('tramitadores_baja', $tramitador), ['motivo_baja' => 'Revocación por la empresa.'])
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

    private function crearPersonaNatural(
        Territorio $territorio,
        string $ci,
        string $nombres,
        string $apellido,
        ?User $usuario = null
    ): Persona {
        $persona = Persona::create([
            'id_usuario' => $usuario?->id,
            'correo' => strtolower($nombres.'.'.$apellido).'@example.com',
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        Natural::create([
            'id_persona' => $persona->id,
            'ci' => $ci,
            'nombres' => $nombres,
            'apellido_paterno' => $apellido,
            'genero' => 1,
        ]);

        return $persona;
    }

    private function crearEmpresaBasica(string $razonSocial): array
    {
        $ambito = Ambito::create(['nombre' => 'Ámbito '.uniqid(), 'estado' => 1]);
        $territorio = Territorio::create([
            'id_ambito' => $ambito->id,
            'nombre' => 'Territorio '.uniqid(),
            'codigo' => uniqid('T-'),
            'estado' => 'ACTIVO',
        ]);
        $tipoEmpresa = TipoEmpresa::create(['descripcion' => 'Tipo '.uniqid(), 'estado' => 'ACTIVO']);
        $personaEmpresa = Persona::create([
            'correo' => uniqid('empresa-').'@example.com',
            'nit' => uniqid('NIT-'),
            'id_territorio' => $territorio->id,
            'estado' => 'ACTIVO',
        ]);
        $empresa = Empresa::create([
            'id_persona' => $personaEmpresa->id,
            'id_tipo_empresa' => $tipoEmpresa->id,
            'razon_social' => $razonSocial,
            'matricula' => uniqid('MAT-'),
            'estado' => 'ACTIVO',
        ]);

        return [$empresa, $territorio];
    }

    private function crearValidador(): User
    {
        $usuario = User::factory()->create(['estado' => 1]);
        Funcionario::create([
            'id_usuario' => $usuario->id,
            'nombres' => 'FUNCIONARIO',
            'apellido_paterno' => 'VALIDADOR',
            'apellido_materno' => 'INSO',
            'ci' => uniqid('CI-'),
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

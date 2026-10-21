<?php

namespace App\Services;

use App\Models\Certificado;
use App\Models\NotificacionTramite;
use App\Models\Persona;
use App\Models\Responsable;
use App\Models\Seguimiento;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class GestionTramitadoresService
{
    /** Gestor externo: último destinatario autorizado; las derivaciones al INSO no lo sustituyen. */
    public function gestorActual(Certificado $certificado): array
    {
        $opciones = $this->destinatariosCorreccion($certificado)->keyBy('id');
        $certificado->loadMissing('seguimientos');
        $opcion = null;
        foreach ($certificado->seguimientos->sortByDesc('id') as $movimiento) {
            if ($movimiento->trashed()) {
                continue;
            }
            $opcion = $opciones->get((int) $movimiento->id_usuario_siguiente);
            if ($opcion) {
                break;
            }
        }

        // Antes de la primera devolución conserva a quien inició, si sigue autorizado.
        $opcion ??= $opciones->first(fn ($item) => (int) $item['id_persona'] === (int) $certificado->id_persona_tramitador);
        if (! $opcion) {
            return ['persona' => null, 'tipo' => 'Sin gestor externo autorizado'];
        }

        $persona = Persona::with(['natural', 'empresa', 'telefonos', 'territorio'])->find($opcion['id_persona']);
        return ['persona' => $persona, 'tipo' => $opcion['tipo']];
    }

    /**
     * Devuelve las cuentas que pueden recibir una correccion del tramite.
     * Primero muestra a quien inició el trámite, si conserva su autorización.
     */
    public function destinatariosCorreccion(Certificado $certificado): Collection
    {
        $certificado->loadMissing([
            'beneficiario.usuario',
            'beneficiario.empresa.responsables.persona.usuario',
            'beneficiario.empresa.responsables.persona.natural',
            'beneficiario.empresa.responsables.rol',
            'tramitador.usuario',
        ]);

        $beneficiario = $certificado->beneficiario;
        if (! $beneficiario) {
            return collect();
        }

        if (! $beneficiario->empresa) {
            $usuarioBeneficiario = $this->usuarioActivo($beneficiario->usuarioAcceso());

            return $usuarioBeneficiario
                ? $this->ordenarDestinatariosPorInicio(collect([$this->opcionDestinatario($usuarioBeneficiario, $beneficiario, 'Persona natural')]), $certificado)
                : collect();
        }

        $destinatarios = collect();

        // La empresa no inicia sesion por si sola: la cuenta principal pertenece a su representante legal.
        $representanteLegal = $beneficiario->empresa->responsables
            ->filter(fn (Responsable $responsable) => $this->responsableTieneRolActivo($responsable, 'solicitante'))
            ->sortByDesc('id')
            ->first();
        $usuarioRepresentante = $this->usuarioActivo($representanteLegal?->persona?->usuario);

        if ($usuarioRepresentante && $representanteLegal?->persona) {
            $destinatarios->push(
                $this->opcionDestinatario($usuarioRepresentante, $representanteLegal->persona, 'Representante legal')
            );
        }

        $beneficiario->empresa->responsables
            ->filter(fn (Responsable $responsable) => $this->responsableEsTramitadorActivo($responsable))
            ->each(function (Responsable $responsable) use ($destinatarios) {
                $usuarioTramitador = $this->usuarioActivo($responsable->persona?->usuario);

                if ($usuarioTramitador) {
                    $destinatarios->push(
                        $this->opcionDestinatario($usuarioTramitador, $responsable->persona, 'Tramitador')
                    );
                }
            });

        return $this->ordenarDestinatariosPorInicio($destinatarios, $certificado);
    }

    /**
     * Propone al iniciador autorizado; en su ausencia, al representante legal disponible.
     */
    public function idDestinatarioPredeterminado(Certificado $certificado): ?int
    {
        $opciones = $this->destinatariosCorreccion($certificado);
        $iniciador = $opciones->firstWhere('inicio_tramite', true);
        if ($iniciador) {
            return $iniciador['id'];
        }
        $representanteLegal = $opciones->firstWhere('tipo', 'Representante legal');

        if ($representanteLegal) {
            return $representanteLegal['id'];
        }

        $primeraOpcion = $opciones->first();

        return $primeraOpcion['id'] ?? null;
    }

    /**
     * Valida en servidor que el destinatario pertenece al beneficiario del tramite.
     */
    public function destinatarioCorreccionValido(Certificado $certificado, ?int $idUsuario): User
    {
        $destinatario = $this->destinatariosCorreccion($certificado)
            ->firstWhere('id', (int) $idUsuario);

        if (! $destinatario) {
            throw ValidationException::withMessages([
                'id_usuario_responsable_correccion' => 'Seleccione al representante legal o a un tramitador activo de la empresa.',
            ]);
        }

        return User::query()->whereKey($destinatario['id'])->where('estado', 1)->firstOrFail();
    }

    /**
     * Define si una cuenta externa puede abrir el tramite desde su bandeja.
     */
    public function usuarioPuedeConsultarTramite(User $usuario, Certificado $certificado): bool
    {
        $certificado->loadMissing('beneficiario.usuario', 'tramitador.usuario');

        if ((int) $certificado->beneficiario?->usuarioAcceso()?->id === (int) $usuario->id) {
            return true;
        }

        return $this->usuarioTieneRelacionActivaConBeneficiario($usuario, $certificado);
    }

    /**
     * El representante legal o tramitador puede responder mientras su vinculo siga activo.
     */
    public function usuarioPuedeResponderCorreccion(User $usuario, Certificado $certificado): bool
    {
        return $this->usuarioPuedeConsultarTramite($usuario, $certificado)
            && (
                (int) $certificado->beneficiario?->usuarioAcceso()?->id === (int) $usuario->id
                || $this->usuarioTieneRelacionActivaConBeneficiario($usuario, $certificado)
            );
    }

    /** Destinatarios habilitados de la misma empresa, excluyendo a la persona que será dada de baja. */
    public function destinatariosBaja(Responsable $tramitador): Collection
    {
        $tramitador->loadMissing('empresa.persona.usuario', 'empresa.responsables.persona.usuario', 'empresa.responsables.persona.natural', 'empresa.responsables.rol');
        $opciones = collect();
        $relaciones = $tramitador->empresa?->responsables ?? collect();
        $representante = $relaciones->filter(fn ($relacion) => $this->responsableTieneRolActivo($relacion, 'solicitante'))->sortByDesc('id')->first();
        foreach ($relaciones as $relacion) {
            if ((int) $relacion->id_persona === (int) $tramitador->id_persona) continue;
            $esRepresentante = $representante && (int) $representante->id === (int) $relacion->id;
            if (! $esRepresentante && ! $this->responsableEsTramitadorActivo($relacion)) continue;
            $usuario = $this->usuarioActivo($relacion->persona?->usuario);
            if ($usuario) {
                $opciones->push($this->opcionDestinatario($usuario, $relacion->persona, $esRepresentante ? 'Representante legal' : 'Tramitador'));
            }
        }
        // Compatibilidad con las cuentas empresariales anteriores al registro de representantes por persona.
        if (! $representante) {
            $beneficiario = $tramitador->empresa?->persona;
            $usuario = $this->usuarioActivo($beneficiario?->usuarioAcceso());
            if ($usuario && (int) $usuario->id !== (int) $tramitador->persona?->id_usuario) {
                $opciones->prepend($this->opcionDestinatario($usuario, $beneficiario, 'Representante legal'));
            }
        }
        return $opciones->unique('id')->sortBy(fn ($opcion) => ($opcion['tipo'] === 'Representante legal' ? '0' : '1').$opcion['nombre'])->values();
    }

    /** Da de baja la relación y transfiere solo sus correcciones pendientes al destinatario elegido. */
    public function darDeBaja(Responsable $tramitador, User $usuarioResponsable, string $motivo, ?int $idDestinatario = null): int
    {
        $datos = \Illuminate\Support\Facades\Validator::make(['motivo_baja' => trim($motivo)], [
            'motivo_baja' => ['required', 'string', 'max:2000'],
        ], [
            'motivo_baja.required' => 'Indique el motivo de la baja.',
            'motivo_baja.max' => 'El motivo de la baja no debe superar los 2000 caracteres.',
        ])->validate();

        if (! Schema::hasColumn('responsables', 'motivo_baja')) {
            throw ValidationException::withMessages([
                'motivo_baja' => 'No se puede registrar la baja: falta habilitar el campo motivo de baja en la base de datos. Comuníquese con el administrador.',
            ]);
        }

        $tramitador->loadMissing('persona.usuario', 'rol', 'empresa.persona.usuario');

        if (! $this->responsableEsTramitadorActivo($tramitador)) {
            throw ValidationException::withMessages([
                'tramitador' => 'El tramitador seleccionado ya no esta activo.',
            ]);
        }

        $pendientes = $this->seguimientosPendientesParaBaja($tramitador);
        $usuarioDestino = null;
        $destinatario = null;
        if ($pendientes->isNotEmpty()) {
            $opciones = $this->destinatariosBaja($tramitador);
            $destinatario = $idDestinatario
                ? $opciones->firstWhere('id', $idDestinatario)
                : $opciones->firstWhere('tipo', 'Representante legal');
            if (! $destinatario) {
                throw ValidationException::withMessages([
                    'id_usuario_destinatario_baja' => 'Seleccione al representante legal o a otro tramitador activo y validado de esta empresa para recibir las correcciones.',
                ]);
            }
            $usuarioDestino = User::findOrFail($destinatario['id']);
        }

        foreach ($pendientes as $seguimiento) {
            $certificado = $seguimiento->certificado;

            // La etapa anterior se cierra y se abre otra para conservar el historial completo.
            $seguimiento->update(['fecha_derivacion' => now()]);

            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Correccion transferida a '.mb_strtolower($destinatario['tipo']).' por baja del tramitador.',
                'referencia' => null,
                'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                'id_usuario_origen' => $usuarioResponsable->id,
                'id_usuario_siguiente' => $usuarioDestino->id,
                'estado' => 'ACTIVO',
            ]);

            $this->crearNotificacion(
                $usuarioDestino,
                $certificado,
                $usuarioResponsable->id,
                'Tramite pendiente de correccion',
                'Tiene una correccion pendiente porque el tramitador fue dado de baja.'
            );
        }

        $idUsuarioTramitador = $tramitador->persona?->id_usuario;

        if ($idUsuarioTramitador && Schema::hasTable('notificaciones_tramites')) {
            NotificacionTramite::query()
                ->where('id_usuario_destino', $idUsuarioTramitador)
                ->where('estado', 'ACTIVO')
                ->whereHas('certificado', fn ($query) => $query->where(
                    'id_persona_beneficiario',
                    $tramitador->empresa?->id_persona
                ))
                ->update(['estado' => 'INACTIVO']);
        }

        $tramitador->update([
            'estado' => 'INACTIVO',
            'fecha_baja' => now()->toDateString(),
            'motivo_baja' => $datos['motivo_baja'],
            'id_usuario_baja' => $usuarioResponsable->id,
        ]);

        return $pendientes->count();
    }

    private function seguimientosPendientesParaBaja(Responsable $tramitador): Collection
    {
        $idUsuarioTramitador = $tramitador->persona?->id_usuario;
        $idBeneficiarioEmpresa = $tramitador->empresa?->id_persona;

        if (! $idUsuarioTramitador || ! $idBeneficiarioEmpresa) {
            return collect();
        }

        return Seguimiento::query()
            ->with(['certificado.beneficiario.usuario'])
            ->where('id_usuario_siguiente', $idUsuarioTramitador)
            ->where('estado', 'ACTIVO')
            ->whereNull('fecha_derivacion')
            ->whereHas('certificado', fn ($query) => $query
                ->where('estado', 'OBSERVADO')
                ->where('id_persona_beneficiario', $idBeneficiarioEmpresa))
            ->get();
    }

    private function usuarioTieneRelacionActivaConBeneficiario(User $usuario, Certificado $certificado): bool
    {
        $idEmpresaBeneficiaria = $certificado->beneficiario?->empresa?->id;

        if (! $idEmpresaBeneficiaria) {
            return false;
        }

        // Comparte la regla usada al iniciar el tramite: persona, empresa, relacion y rol activos.
        return $usuario->relacionesEmpresarialesParaTramites()
            ->contains(fn (Responsable $relacion) => (int) $relacion->id_empresa === (int) $idEmpresaBeneficiaria
                && ($relacion->rol?->slug === 'solicitante'
                    || (int) $certificado->id_usuario_registro === (int) $usuario->id
                    || $certificado->seguimientos()->where('id_usuario_siguiente', $usuario->id)->exists()));
    }

    private function responsableEsTramitadorActivo(Responsable $responsable): bool
    {
        return $this->responsableTieneRolActivo($responsable, 'tramitador');
    }

    private function responsableTieneRolActivo(Responsable $responsable, string $slug): bool
    {
        $rol = $responsable->rol;

        return in_array((string) $responsable->estado, ['1', 'ACTIVO'], true)
            && $responsable->persona
            && in_array((string) $responsable->persona->estado, ['1', 'ACTIVO'], true)
            && $rol
            && (string) $rol->estado === '1'
            && $rol->slug === $slug;
    }

    private function usuarioActivo(?User $usuario): ?User
    {
        return $usuario && in_array((string) $usuario->estado, ['1', 'ACTIVO'], true)
            ? $usuario
            : null;
    }

    private function ordenarDestinatariosPorInicio(Collection $opciones, Certificado $certificado): Collection
    {
        $idIniciador = $certificado->id_usuario_registro
            ?: $certificado->seguimientos()->orderBy('id')->value('id_usuario_origen');

        return $opciones->unique('id')->map(function (array $opcion) use ($idIniciador) {
            $opcion['inicio_tramite'] = $idIniciador && (int) $opcion['id'] === (int) $idIniciador;
            $opcion['etiqueta'] = $opcion['tipo'] . ($opcion['inicio_tramite'] ? ' (inició el trámite)' : '');
            return $opcion;
        })->sortByDesc('inicio_tramite')->values();
    }

    private function opcionDestinatario(User $usuario, Persona $persona, string $tipo): array
    {
        return [
            'id' => $usuario->id,
            'id_persona' => $persona->id,
            'nombre' => $this->nombrePersona($persona),
            'tipo' => $tipo,
            'busqueda' => mb_strtolower($this->nombrePersona($persona).' '.$tipo),
        ];
    }

    private function nombrePersona(Persona $persona): string
    {
        if ($persona->empresa) {
            return $persona->empresa->razon_social;
        }

        return trim(implode(' ', array_filter([
            $persona->natural?->nombres,
            $persona->natural?->apellido_paterno,
            $persona->natural?->apellido_materno,
        ]))) ?: 'Sin nombre';
    }

    private function crearNotificacion(User $usuario, Certificado $certificado, int $idUsuarioEmisor, string $titulo, string $mensaje): void
    {
        if (! Schema::hasTable('notificaciones_tramites')) {
            return;
        }

        $datos = [
            'id_usuario_destino' => $usuario->id,
            'id_usuario_emisor' => $idUsuarioEmisor,
            'id_certificado' => $certificado->id,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'estado' => 'ACTIVO',
        ];

        if (! Schema::hasColumn('notificaciones_tramites', 'id_usuario_emisor')) {
            unset($datos['id_usuario_emisor']);
        }

        NotificacionTramite::create($datos);
    }
}

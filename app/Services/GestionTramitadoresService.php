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
    /**
     * Devuelve las cuentas que pueden recibir una correccion del tramite.
     * En empresas se muestra primero al representante legal y luego a sus tramitadores activos.
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
                ? collect([$this->opcionDestinatario($usuarioBeneficiario, $beneficiario, 'Beneficiario y tramitador')])
                : collect();
        }

        $destinatarios = collect();

        // La empresa no inicia sesion por si sola: la cuenta principal pertenece a su representante legal.
        $representanteLegal = $beneficiario->empresa->responsables
            ->filter(fn (Responsable $responsable) => $this->responsableTieneRolActivo($responsable, 'representante-legal'))
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

        return $destinatarios
            ->unique('id')
            ->values();
    }

    /**
     * Para empresas propone primero al representante legal; en los demás casos conserva al tramitador original.
     */
    public function idDestinatarioPredeterminado(Certificado $certificado): ?int
    {
        $opciones = $this->destinatariosCorreccion($certificado);
        $representanteLegal = $opciones->firstWhere('tipo', 'Representante legal');

        if ($representanteLegal) {
            return $representanteLegal['id'];
        }

        $idTramitador = (int) ($certificado->tramitador?->id_usuario ?? 0);

        if ($idTramitador && $opciones->contains('id', $idTramitador)) {
            return $idTramitador;
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

    /**
     * Da de baja al tramitador y transfiere sus correcciones pendientes al beneficiario.
     */
    public function darDeBaja(Responsable $tramitador, User $usuarioResponsable): int
    {
        $tramitador->loadMissing('persona.usuario', 'rol', 'empresa.persona.usuario');

        if (! $this->responsableEsTramitadorActivo($tramitador)) {
            throw ValidationException::withMessages([
                'tramitador' => 'El tramitador seleccionado ya no esta activo.',
            ]);
        }

        $pendientes = $this->seguimientosPendientesParaBaja($tramitador);
        $this->validarBeneficiariosConAcceso($pendientes);

        foreach ($pendientes as $seguimiento) {
            $certificado = $seguimiento->certificado;
            $usuarioBeneficiario = $certificado->beneficiario?->usuarioAcceso();

            // La etapa anterior se cierra y se abre otra para conservar el historial completo.
            $seguimiento->update(['fecha_derivacion' => now()->toDateString()]);

            Seguimiento::create([
                'id_seguimiento_padre' => $seguimiento->id,
                'id_certificado' => $certificado->id,
                'fecha_inicio' => now()->toDateString(),
                'fecha_derivacion' => null,
                'fecha_final' => null,
                'descripcion_final' => 'Correccion transferida al beneficiario por baja del tramitador.',
                'referencia' => 'Baja del tramitador',
                'id_usuario_anterior' => $seguimiento->id_usuario_siguiente,
                'id_usuario_origen' => $usuarioResponsable->id,
                'id_usuario_siguiente' => $usuarioBeneficiario->id,
                'estado' => 'ACTIVO',
            ]);

            $this->crearNotificacion(
                $usuarioBeneficiario,
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
            'id_usuario_baja' => $usuarioResponsable->id,
        ]);

        return $pendientes->count();
    }

    private function seguimientosPendientesParaBaja(Responsable $tramitador): Collection
    {
        $idUsuarioTramitador = $tramitador->persona?->id_usuario;

        if (! $idUsuarioTramitador) {
            return collect();
        }

        return Seguimiento::query()
            ->with(['certificado.beneficiario.usuario'])
            ->where('id_usuario_siguiente', $idUsuarioTramitador)
            ->where('estado', 'ACTIVO')
            ->whereNull('fecha_derivacion')
            ->whereHas('certificado', fn ($query) => $query->where('estado', 'OBSERVADO'))
            ->get();
    }

    private function validarBeneficiariosConAcceso(Collection $pendientes): void
    {
        $sinAcceso = $pendientes->first(function (Seguimiento $seguimiento) {
            return ! $this->usuarioActivo($seguimiento->certificado?->beneficiario?->usuarioAcceso());
        });

        if ($sinAcceso) {
            throw ValidationException::withMessages([
                'tramitador' => 'No se puede dar de baja porque el beneficiario no tiene una cuenta activa para recibir la correccion.',
            ]);
        }
    }

    private function usuarioTieneRelacionActivaConBeneficiario(User $usuario, Certificado $certificado): bool
    {
        $idEmpresaBeneficiaria = $certificado->beneficiario?->empresa?->id;

        if (! $idEmpresaBeneficiaria) {
            return false;
        }

        // Comparte la regla usada al iniciar el tramite: persona, empresa, relacion y rol activos.
        return $usuario->relacionesEmpresarialesParaTramites()
            ->contains(fn (Responsable $relacion) => (int) $relacion->id_empresa === (int) $idEmpresaBeneficiaria);
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

    private function opcionDestinatario(User $usuario, Persona $persona, string $tipo): array
    {
        return [
            'id' => $usuario->id,
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

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
     * Para empresas incluye al beneficiario y a sus tramitadores activos.
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
        $usuarioBeneficiario = $this->usuarioActivo($beneficiario?->usuario);

        if (! $beneficiario) {
            return collect();
        }

        if (! $beneficiario->empresa) {
            return $usuarioBeneficiario
                ? collect([$this->opcionDestinatario($usuarioBeneficiario, $beneficiario, 'Beneficiario y tramitador')])
                : collect();
        }

        $destinatarios = collect();

        if ($usuarioBeneficiario) {
            $destinatarios->push($this->opcionDestinatario($usuarioBeneficiario, $beneficiario, 'Beneficiario'));
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
     * Prefiere al tramitador original cuando sigue habilitado; si no, usa al beneficiario.
     */
    public function idDestinatarioPredeterminado(Certificado $certificado): ?int
    {
        $opciones = $this->destinatariosCorreccion($certificado);
        $idTramitador = (int) ($certificado->tramitador?->id_usuario ?? 0);

        if ($idTramitador && $opciones->contains('id', $idTramitador)) {
            return $idTramitador;
        }

        $beneficiario = $opciones->firstWhere('tipo', 'Beneficiario');
        $primeraOpcion = $opciones->first();

        return $beneficiario['id'] ?? $primeraOpcion['id'] ?? null;
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
                'id_usuario_responsable_correccion' => 'Seleccione un beneficiario o tramitador activo de la empresa.',
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

        if ((int) $certificado->beneficiario?->id_usuario === (int) $usuario->id) {
            return true;
        }

        if (! $this->usuarioEsTramitadorActivoDelBeneficiario($usuario, $certificado)) {
            return false;
        }

        if ((int) $certificado->tramitador?->id_usuario === (int) $usuario->id) {
            return true;
        }

        return $certificado->seguimientos()
            ->where('id_usuario_siguiente', $usuario->id)
            ->where('estado', 'ACTIVO')
            ->whereNull('fecha_derivacion')
            ->exists();
    }

    /**
     * Un tramitador puede responder solo mientras siga activo para la empresa beneficiaria.
     */
    public function usuarioPuedeResponderCorreccion(User $usuario, Certificado $certificado): bool
    {
        return $this->usuarioPuedeConsultarTramite($usuario, $certificado)
            && (
                (int) $certificado->beneficiario?->id_usuario === (int) $usuario->id
                || $this->usuarioEsTramitadorActivoDelBeneficiario($usuario, $certificado)
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
            $usuarioBeneficiario = $certificado->beneficiario?->usuario;

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
            return ! $this->usuarioActivo($seguimiento->certificado?->beneficiario?->usuario);
        });

        if ($sinAcceso) {
            throw ValidationException::withMessages([
                'tramitador' => 'No se puede dar de baja porque el beneficiario no tiene una cuenta activa para recibir la correccion.',
            ]);
        }
    }

    private function usuarioEsTramitadorActivoDelBeneficiario(User $usuario, Certificado $certificado): bool
    {
        $beneficiario = $certificado->beneficiario;
        $empresa = $beneficiario?->empresa;
        $personaUsuario = $usuario->persona;

        if (! $empresa || ! $personaUsuario) {
            return false;
        }

        return Responsable::query()
            ->where('id_empresa', $empresa->id)
            ->where('id_persona', $personaUsuario->id)
            ->whereIn('estado', ['1', 'ACTIVO'])
            ->whereHas('rol', function ($query) {
                $query->where('estado', 1)
                    ->where(function ($rol) {
                        $rol->where('slug', 'tramitador')
                            ->orWhere('name', 'like', '%TRAMITADOR%');
                    });
            })
            ->exists();
    }

    private function responsableEsTramitadorActivo(Responsable $responsable): bool
    {
        $rol = $responsable->rol;

        return in_array((string) $responsable->estado, ['1', 'ACTIVO'], true)
            && $rol
            && (string) $rol->estado === '1'
            && ($rol->slug === 'tramitador' || str_contains(mb_strtoupper((string) $rol->name), 'TRAMITADOR'));
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
            'busqueda' => mb_strtolower($this->nombrePersona($persona) . ' ' . $tipo),
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

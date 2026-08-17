<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NotificacionTramite extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'notificaciones_tramites';

    protected $fillable = [
        'id_usuario_destino',
        'id_usuario_emisor',
        'id_certificado',
        'titulo',
        'mensaje',
        'fecha_visto',
        'estado',
    ];

    protected $casts = [
        'fecha_visto' => 'datetime',
    ];

    /**
     * Prepara los datos visibles del solicitante y de la persona que hizo el envío.
     */
    public function datosPresentacion(): array
    {
        $this->loadMissing(
            'usuarioEmisor.funcionario.cargos',
            'usuarioEmisor.persona.empresa',
            'usuarioEmisor.persona.natural',
            'certificado.beneficiario.natural',
            'certificado.beneficiario.empresa.responsables.persona.natural',
            'certificado.tramitador.natural',
            'certificado.tramitador.empresa'
        );

        $certificado = $this->certificado;
        $remitente = $this->datosRemitente($this->usuarioEmisor);

        if (! $certificado) {
            return [
                'tipo_solicitante' => null,
                'solicitante' => $this->mensaje ?: 'Solicitud de tramitador',
                'enviado_por' => $remitente['nombre'],
                'actua_como' => $remitente['detalle'],
            ];
        }

        $beneficiario = $certificado->beneficiario;
        $tramitador = $certificado->tramitador;
        $esEmpresa = (bool) $beneficiario?->empresa;
        $tipoSolicitante = $esEmpresa ? 'Empresa' : 'Persona natural';

        // Un funcionario puede generar avisos posteriores sobre el mismo trámite.
        if ($this->usuarioEmisor?->funcionario) {
            return [
                'tipo_solicitante' => $tipoSolicitante,
                'solicitante' => $this->nombrePersona($beneficiario),
                'enviado_por' => $remitente['nombre'],
                'actua_como' => $remitente['detalle'],
            ];
        }

        if (! $esEmpresa) {
            return [
                'tipo_solicitante' => $tipoSolicitante,
                'solicitante' => $this->nombrePersona($beneficiario),
                'enviado_por' => 'El mismo solicitante',
                'actua_como' => 'Solicitante',
            ];
        }

        // Si la persona tramitadora es distinta de la empresa, actúa como tramitador autorizado.
        if ($tramitador && (int) $tramitador->id !== (int) $beneficiario?->id) {
            return [
                'tipo_solicitante' => $tipoSolicitante,
                'solicitante' => $this->nombrePersona($beneficiario),
                'enviado_por' => $this->nombrePersona($tramitador),
                'actua_como' => 'Tramitador autorizado',
            ];
        }

        $responsableActivo = $beneficiario?->empresa?->responsables
            ?->first(fn ($responsable) => in_array(
                mb_strtoupper((string) $responsable->estado),
                ['1', 'ACTIVO'],
                true
            ));

        return [
            'tipo_solicitante' => $tipoSolicitante,
            'solicitante' => $this->nombrePersona($beneficiario),
            'enviado_por' => $responsableActivo
                ? $this->nombrePersona($responsableActivo->persona)
                : 'Sin responsable activo',
            'actua_como' => 'Responsable o representante legal',
        ];
    }

    /**
     * Identifica a la persona que atendio la notificacion.
     * El destinatario es quien puede realizar la accion dentro del tramite.
     */
    public function datosAtencion(): array
    {
        $this->loadMissing(
            'usuarioDestino.funcionario.cargos',
            'usuarioDestino.persona.empresa',
            'usuarioDestino.persona.natural'
        );

        return $this->datosRemitente($this->usuarioDestino);
    }

    /**
     * Obtiene el nombre y el cargo cuando el aviso fue generado por un usuario interno.
     */
    private function datosRemitente(?User $usuario): array
    {
        if (! $usuario) {
            return ['nombre' => 'Sin remitente', 'detalle' => 'Sin dato'];
        }

        if ($usuario->funcionario) {
            $nombreFuncionario = trim(implode(' ', array_filter([
                $usuario->funcionario->nombres,
                $usuario->funcionario->apellido_paterno,
                $usuario->funcionario->apellido_materno,
            ])));

            return [
                'nombre' => $nombreFuncionario ?: ($usuario->name ?: 'Sin funcionario'),
                'detalle' => $usuario->funcionario->cargos?->pluck('nombre')->filter()->implode(', ') ?: 'Sin cargo',
            ];
        }

        return [
            'nombre' => $this->nombrePersona($usuario->persona),
            'detalle' => $usuario->persona?->empresa ? 'Empresa' : 'Persona natural',
        ];
    }

    /**
     * Devuelve la razón social o el nombre completo de una persona.
     */
    private function nombrePersona(?Persona $persona): string
    {
        if ($persona?->empresa) {
            return $persona->empresa->razon_social ?: 'Empresa sin razón social';
        }

        if ($persona?->natural) {
            return trim(implode(' ', array_filter([
                $persona->natural->nombres,
                $persona->natural->apellido_paterno,
                $persona->natural->apellido_materno,
            ]))) ?: 'Persona natural sin nombre';
        }

        return 'Sin persona';
    }

    // Usuario que recibe la notificacion en la campana.
    public function usuarioDestino()
    {
        return $this->belongsTo(User::class, 'id_usuario_destino');
    }

    // Usuario que genero o envio la notificacion.
    public function usuarioEmisor()
    {
        return $this->belongsTo(User::class, 'id_usuario_emisor');
    }

    // Solicitud/tramite relacionado a la notificacion.
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

}

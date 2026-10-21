<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Certificado extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'certificados';
    protected $fillable = [
        'id_tipo_certificado',
        'id_persona_beneficiario',
        'id_persona_tramitador',
        'descripcion',
        'estado',
    ];

    public const ESTADOS_CERTIFICADO = [
        'EN_REVISION' => 'EN REVISIÓN',
        'OBSERVADO'   => 'OBSERVADO',
        'APROBADO'    => 'APROBADO',
        'RECHAZADO'   => 'RECHAZADO',
        'EMITIDO'     => 'EMITIDO',
        'VENCIDO'     => 'VENCIDO',
        'ANULADO'     => 'ANULADO',
        'PENDIENTE_PAGO' => 'PENDIENTE DE PAGO', // El trámite fue guardado, pero todavía debe confirmarse el pago.
        'PAGO_CONFIRMADO' => 'PAGO CONFIRMADO', // El pago fue confirmado, pero el usuario todavía no envió la solicitud.            
    ];    


    // La fecha final representa la vigencia del documento, no el cierre del tramite.
    // Solo un certificado ya emitido puede vencer; si vence hoy sigue vigente durante el dia.
    public function debeMarcarseComoVencido(): bool
    {
        return false;
    }


    // Verifica si todos los requisitos cargados para este certificado estan cumplidos.
    public function cumpleTodosLosRequisitos(): bool
    {
        $requisitos = $this->certificadoRequisitos()->get();

        return $requisitos->isNotEmpty()
            && $requisitos->every(fn ($requisito) => $requisito->cumple === 'SI');
    }


    /**
     * Calcula el resultado general de los requisitos evaluados con calificación.
     *
     * Usa la última calificación activa de cada requisito y la compara con la calificación
     * mínima configurada.
     *
     * Resultado:
     * - PENDIENTE: falta registrar alguna calificación.
     * - APROBADO: todas las calificaciones alcanzan el mínimo.
     * - PARTICIPACION: alguna calificación no alcanza el mínimo.
     */
    public function resumenEvaluacionCalificaciones(): array
    {
        // Obtiene los requisitos activos del certificado que se evalúan con calificación.
        $configuraciones = RequisitoTipoCertificado::query()
            ->with('tipoEvidencia')
            ->where('id_tipo_certificado', $this->id_tipo_certificado)
            ->where('estado', 'ACTIVO')
            ->whereHas(
                'tipoEvidencia',
                fn ($query) => $query->where('codigo', 'CALIFICACION')
            )
            ->orderBy('orden')
            ->get();

        // La evaluación no aplica si no existen requisitos de tipo CALIFICACION.
        if ($configuraciones->isEmpty()) {
            return [
                'aplica' => false,
                'resultado' => null,
                'detalles' => collect(),
            ];
        }

        // Carga los requisitos del trámite y sus revisiones.
        $requisitos = $this->certificadoRequisitos()
            ->with('revisionesRequisitos')
            ->get()
            ->keyBy('id_requisito');

        // Evalúa la última calificación registrada para cada requisito.
        $detalles = $configuraciones->map(
            function ($configuracion) use ($requisitos) {
                $requisito = $requisitos->get(
                    $configuracion->id_requisito
                );

                // Toma la última revisión activa que tenga una calificación.
                $revision = $requisito
                    ? $requisito->revisionesRequisitos
                        ->where('estado', 'ACTIVO')
                        ->whereNotNull('calificacion_obtenida')
                        ->sortByDesc('id')
                        ->first()
                    : null;

                $calificacion = $revision?->calificacion_obtenida;
                $minima = $configuracion->calificacion_minima_aprobacion;

                return [
                    'id_requisito' => $configuracion->id_requisito,
                    'calificacion_obtenida' => $calificacion,
                    'calificacion_minima' => $minima,

                    // Aprueba cuando existe calificación y alcanza el mínimo configurado.
                    'aprobado' => $calificacion !== null
                        && $minima !== null
                        && (float) $calificacion >= (float) $minima,
                ];
            }
        );

        // Si falta una calificación queda pendiente; de lo contrario verifica si todas aprobaron.
        $resultado = $detalles->contains(fn ($detalle) => $detalle['calificacion_obtenida'] === null) ? 'PENDIENTE' : (
            $detalles->every(fn ($detalle) => $detalle['aprobado']) ? 'APROBADO' : 'PARTICIPACION'
        );

        return [
            'aplica' => true,
            'resultado' => $resultado,
            'detalles' => $detalles,
        ];
    }


    // Define si el tramite ya esta listo para emitir el certificado.
    // Se centraliza aqui para usar la misma regla en vista, controlador y futuras plantillas.
    public function puedeEmitirse(): bool
    {
        $tramiteFinalizado = $this->seguimientos()
            ->where('estado', 'FINALIZADO')
            ->exists();

        return $this->cumpleTodosLosRequisitos()
            && (in_array($this->estado, ['APROBADO', 'EMITIDO'], true) || $tramiteFinalizado)
            && !$this->debeMarcarseComoVencido()
            && !in_array($this->estado, ['ANULADO', 'RECHAZADO'], true);
    }


    // Verifica si el tipo de certificado tiene configurada una evidencia especifica.
    public function requiereEvidencia(string|array $codigos): bool
    {
        $codigos = collect((array) $codigos)
            ->map(fn ($codigo) => strtoupper(trim((string) $codigo)))
            ->filter()
            ->values();

        if ($codigos->isEmpty() || !$this->id_tipo_certificado) {
            return false;
        }

        return RequisitoTipoCertificado::query()
            ->where('id_tipo_certificado', $this->id_tipo_certificado)
            ->where('estado', 'ACTIVO')
            ->whereHas('tipoEvidencia', function ($query) use ($codigos) {
                $query->whereIn('codigo', $codigos);
            })
            ->exists();
    }


    // Verifica si existe al menos un requisito pendiente o no cumplido.
    public function tieneRequisitosObservados(): bool
    {
        return $this->certificadoRequisitos()
            ->where('estado', 'OBSERVADO')
            ->where(function ($query) {
                $query->whereNull('cumple')
                    ->orWhere('cumple', '!=', 'SI');
            })
            ->exists();
    }


    // Calcula el estado correcto segun requisitos y vigencia.
    // Prioridad: vencido por fecha, observado por requisitos, aprobado si cumple todo.
    public function estadoSegunVigencia(?string $estadoSolicitado = null): string
    {
        $estadoBase = $estadoSolicitado ?: $this->estado;

        // Estos estados son decisiones finales y no deben cambiar por fecha ni requisitos.
        if (in_array($estadoBase, ['ANULADO', 'RECHAZADO'], true)) {
            return $estadoBase;
        }

        if ($this->debeMarcarseComoVencido()) {
            return 'VENCIDO';
        }

        if ($estadoBase === 'EMITIDO' && $this->cumpleTodosLosRequisitos()) {
            return 'EMITIDO';
        }

        if ($this->tieneRequisitosObservados()) {
            return 'OBSERVADO';
        }

        // Cumplir requisitos no aprueba por si solo: el cierre se hace con "Finalizar tramite".
        return $estadoBase === 'VENCIDO' ? 'EN_REVISION' : $estadoBase;
    }


    // Sincroniza el estado despues de crear, editar o cargar datos del listado.
    public function sincronizarEstadoPorVigencia(?string $estadoSolicitado = null): bool
    {
        $estadoCorrecto = $this->estadoSegunVigencia($estadoSolicitado);

        if ($this->estado === $estadoCorrecto) {
            return false;
        }

        return $this->update(['estado' => $estadoCorrecto]);
    }


    // Trabaja sobre un solo certificado.
    // --- Mantiene compatibilidad con llamadas anteriores del controlador ---
    public function actualizarEstadoVencido(): bool
    {
        return $this->sincronizarEstadoPorVigencia();
    }


    // Trabaja sobre todos los certificados
    // --- Revisa el listado: vence por fecha y ajusta APROBADO/OBSERVADO segun requisitos --- 
    public static function actualizarCertificadosEstadosVencidos(): int
    {
        $datosObservado = ['estado' => 'OBSERVADO'];

        // Antes de emitirse, el estado observado depende de los requisitos y no de la vigencia.
        $observados = static::query()
            ->whereNotIn('estado', ['ANULADO', 'RECHAZADO', 'EMITIDO'])
            ->where(function ($query) {
                $query->whereNull('estado')
                    ->orWhere('estado', '!=', 'OBSERVADO');
            })
            ->whereHas('certificadoRequisitos', function ($query) {
                $query->where('estado', 'OBSERVADO')
                    ->where(function ($query) {
                        $query->whereNull('cumple')
                            ->orWhere('cumple', '!=', 'SI');
                    });
            })
            ->update($datosObservado);

        return $observados;
    }


    // Devuelve colores suaves para mostrar estados como chips informativos.
    public static function claseEstadoCertificado(?string $estado): string
    {
        return match ($estado) {
            'ACTIVO', 'APROBADO', 'EMITIDO' => 'bg-emerald-100 text-emerald-700',
            'VENCIDO', 'ANULADO', 'RECHAZADO' => 'bg-rose-100 text-rose-700',
            'OBSERVADO' => 'bg-amber-100 text-amber-700',
            'EN_REVISION' => 'bg-cyan-100 text-cyan-700',
            default => 'bg-slate-100 text-slate-600',
        };
    }

    // Traduce el codigo guardado en base de datos a un texto claro para la interfaz.
    public static function textoEstadoCertificado(?string $estado): string
    {
        if (!$estado) {
            return 'Sin estado';
        }

        return match ($estado) {
            'EN_REVISION' => 'En revisión',
            'OBSERVADO' => 'Observado',
            'APROBADO' => 'Aprobado',
            'RECHAZADO' => 'Rechazado',
            'EMITIDO' => 'Emitido',
            'VENCIDO' => 'Vencido',
            'ANULADO' => 'Anulado',
            'ACTIVO' => 'Activo',
            default => str_replace('_', ' ', ucfirst(strtolower($estado))),
        };
    }

    // Icono unico por estado para que los chips sean mas faciles de escanear.
    public static function iconoEstadoCertificado(?string $estado): string
    {
        return match ($estado) {
            'ACTIVO', 'APROBADO', 'EMITIDO' => 'fa-solid fa-circle-check',
            'OBSERVADO' => 'fa-solid fa-triangle-exclamation',
            'EN_REVISION' => 'fa-regular fa-clock',
            'VENCIDO' => 'fa-solid fa-hourglass-end',
            'ANULADO', 'RECHAZADO' => 'fa-solid fa-circle-xmark',
            default => 'fa-regular fa-circle',
        };
    }

    

    //---------------------------------------------------------------------------------------------------------------------------
    //RELACIONES

    // Relación muchos a uno (muchos certificados pertenecen a un tipo de certificado)
    public function tipoCertificado()
    {
        return $this->belongsTo(TipoCertificado::class, 'id_tipo_certificado');
    }

    // Relación muchos a uno (muchos certificados tienen una persona beneficiaria)
    public function beneficiario()
    {
        return $this->belongsTo(Persona::class, 'id_persona_beneficiario');
    }

    // Relación muchos a uno (muchos certificados tienen una persona tramitadora)
    public function tramitador()
    {
        return $this->belongsTo(Persona::class, 'id_persona_tramitador');
    }

    // Relación uno a muchos (un certificado tiene muchos requisitos)
    public function certificadoRequisitos()
    {
        return $this->hasMany(RequisitoCertificado::class, 'id_certificado')
            ->orderBy('orden')
            ->orderBy('id');
    }

    // Relación muchos a muchos (muchos certificados tienen muchos requisitos)
    public function requisitos()
    {
        return $this->belongsToMany(
            Requisito::class,
            'requisitos_certificados',
            'id_certificado',
            'id_requisito'
        )
            ->withPivot('id', 'orden', 'cumple', 'estado')
            ->orderByPivot('orden')
            ->orderByPivot('id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    // Relacion uno a muchos (un certificado tiene muchas asignaciones con registros)
    public function certificadosRegistros()
    {
        return $this->hasMany(CertificadoRegistro::class, 'id_certificado');
    }

    // Relación muchos a muchos (muchos certificados pertenecen a muchos registros)
    public function registros()
    {
        return $this->belongsToMany(
            Registro::class,
            'certificados_registros',
            'id_certificado',
            'id_registro'
        )
            ->withPivot('id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    // Relacion uno a muchos (un certificado tiene muchas asignaciones de pagos)
    public function pagosCertificados()
    {
        return $this->hasMany(PagoCertificado::class, 'id_certificado');
    }

    // Relacion muchos a muchos (muchos certificados pertenecen a muchos pagos)
    public function pagos()
    {
        return $this->belongsToMany(
            Pago::class,
            'pagos_certificados',
            'id_certificado',
            'id_pago'
        )
            ->withPivot('id')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    // Relacion uno a muchos (un certificado tiene muchos seguimientos)
    public function seguimientos()
    {
        return $this->hasMany(Seguimiento::class, 'id_certificado');
    }

    // Historial de usuarios que descargaron el documento emitido.
    public function descargas()
    {
        return $this->hasMany(DescargaCertificado::class, 'id_certificado');
    }

    // Documentos oficiales emitidos desde este trámite.
    public function emisiones()
    {
        return $this->hasMany(CertificadoEmitido::class, 'id_certificado')
            ->orderBy('orden_emision')
            ->orderBy('id');
    }

    // Compatibilidad visual: el trámite se identifica por su ID, no guarda código propio.
    public function getCodigoAttribute(): string
    {
        return $this->id ? (string) $this->id : 'Sin código';
    }

    // Las fechas y PDF oficiales pertenecen a certificados_emitidos.
    // Estos accesores evitan consultas a columnas eliminadas en vistas antiguas.
    public function getFechaInicioAttribute()
    {
        return $this->created_at;
    }

    public function getFechaFinAttribute()
    {
        return $this->relationLoaded('emisiones')
            ? $this->emisiones->last()?->fecha_fin
            : null;
    }

    public function getUrlDocumentoAttribute(): ?string
    {
        return $this->relationLoaded('emisiones')
            ? $this->emisiones->last()?->url_documento
            : null;
    }

}

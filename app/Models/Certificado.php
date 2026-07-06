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
        'codigo',
        'fecha_inicio',
        'fecha_fin',
        'descripcion',
        'url_documento',
        'estado',
    ];

    // Convierte fechas a Carbon para mostrarlas y formatearlas sin repetir parseos.
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
    ];

    public const ESTADOS_CERTIFICADO = [
        'EN_REVISION' => 'EN REVISIÓN',
        'OBSERVADO'   => 'OBSERVADO',
        'APROBADO'    => 'APROBADO',
        'RECHAZADO'   => 'RECHAZADO',
        'EMITIDO'     => 'EMITIDO',
        'VENCIDO'     => 'VENCIDO',
        'ANULADO'     => 'ANULADO',
    ];    


    // Verifica si el certificado ya paso su fecha final.
    // Se usa menor a hoy: si vence hoy, todavia se considera vigente durante el dia.
    public function debeMarcarseComoVencido(): bool
    {
        return $this->fecha_fin
            && $this->fecha_fin->toDateString() < now()->toDateString()
            && !in_array($this->estado, ['ANULADO', 'RECHAZADO'], true);
    }


    // Verifica si todos los requisitos cargados para este certificado estan cumplidos.
    public function cumpleTodosLosRequisitos(): bool
    {
        $requisitos = $this->certificadoRequisitos()->get();

        return $requisitos->isNotEmpty()
            && $requisitos->every(fn ($requisito) => $requisito->cumple === 'SI');
    }


    // Define si el tramite ya esta listo para emitir el certificado.
    // Se centraliza aqui para usar la misma regla en vista, controlador y futuras plantillas.
    public function puedeEmitirse(): bool
    {
        return $this->cumpleTodosLosRequisitos()
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

        if ($this->cumpleTodosLosRequisitos()) {
            return 'APROBADO';
        }

        // Si todavia no tiene requisitos cargados, se respeta el estado elegido en el formulario.
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
        $datosVencido = ['estado' => 'VENCIDO'];
        $datosAprobado = ['estado' => 'APROBADO'];
        $datosObservado = ['estado' => 'OBSERVADO'];

        // Marca como vencidos los certificados cuya fecha final ya paso.
        $vencidos = static::query()
            ->whereNotNull('fecha_fin')
            ->whereNotIn('estado', ['ANULADO', 'RECHAZADO'])
            ->where(function ($query) {
                $query->whereNull('estado')
                    ->orWhere('estado', '!=', 'VENCIDO');
            })
            ->whereDate('fecha_fin', '<', now()->toDateString())
            ->update($datosVencido);

        // Si no vencio y tiene algun requisito no cumplido, el certificado queda observado.
        $observados = static::query()
            ->whereNotIn('estado', ['ANULADO', 'RECHAZADO', 'EMITIDO'])
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
            })
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

        // Si no vencio y todos los requisitos cumplen, el certificado queda aprobado.
        $aprobados = static::query()
            ->whereNotIn('estado', ['ANULADO', 'RECHAZADO', 'EMITIDO'])
            ->where(function ($query) {
                $query->whereNull('fecha_fin')
                    ->orWhereDate('fecha_fin', '>=', now()->toDateString());
            })
            ->where(function ($query) {
                $query->whereNull('estado')
                    ->orWhere('estado', '!=', 'APROBADO');
            })
            ->whereHas('certificadoRequisitos')
            ->whereDoesntHave('certificadoRequisitos', function ($query) {
                $query->whereNull('cumple')
                    ->orWhere('cumple', '!=', 'SI');
            })
            ->update($datosAprobado);

        return $vencidos + $observados + $aprobados;
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
        return $this->hasMany(RequisitoCertificado::class, 'id_certificado');
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
            ->withPivot('id', 'cumple', 'estado')
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
}

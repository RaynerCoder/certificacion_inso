<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Documento oficial generado desde un trámite.
 *
 * Un trámite puede producir un solo documento o varios. Cuando el tipo se
 * emite por lote, cada documento apunta a la fila exacta de certificados_registros.
 */
class CertificadoEmitido extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'certificados_emitidos';

    protected $fillable = [
        'id_certificado',
        'id_certificado_registro',
        'id_plantilla_certificado',
        'orden_emision',
        'numero_certificado',
        'codigo_verificacion',
        'fecha_emision',
        'fecha_inicio',
        'fecha_fin',
        'vigencia_indefinida',
        'url_documento',
        'estado',
    ];

    protected $casts = [
        'fecha_emision' => 'datetime',
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'vigencia_indefinida' => 'boolean',
    ];

    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    public function certificadoRegistro()
    {
        return $this->belongsTo(CertificadoRegistro::class, 'id_certificado_registro');
    }

    public function plantillaCertificado()
    {
        return $this->belongsTo(PlantillaCertificado::class, 'id_plantilla_certificado');
    }

    public function descargas()
    {
        return $this->hasMany(DescargaCertificado::class, 'id_certificado_emitido');
    }

    public function debeMarcarseComoVencido(): bool
    {
        return $this->estado === 'EMITIDO'
            && $this->fecha_fin
            && $this->fecha_fin->isBefore(today());
    }

    // La vigencia pertenece a cada documento emitido, no al trámite que lo agrupa.
    public static function actualizarEstadosVencidos(): int
    {
        return static::query()
            ->where('estado', 'EMITIDO')
            ->whereNotNull('fecha_fin')
            ->whereDate('fecha_fin', '<', today())
            ->update(['estado' => 'VENCIDO']);
    }
}

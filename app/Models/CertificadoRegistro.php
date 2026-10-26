<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CertificadoRegistro extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'certificados_registros';
    protected $fillable = [
        'id',
        'id_certificado',
        'id_registro',
    ];

    // Relacion muchos a uno (muchas asignaciones pertenecen a un certificado)
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    // Relacion muchos a uno (muchas asignaciones pertenecen a un registro)
    public function registro()
    {
        return $this->belongsTo(Registro::class, 'id_registro');
    }

    // Historial completo de documentos emitidos para este lote. Puede haber más
    // de uno cuando un documento anterior se anuló y luego se reemitió.
    public function emisiones()
    {
        return $this->hasMany(CertificadoEmitido::class, 'id_certificado_registro')
            ->orderBy('orden_emision')
            ->orderBy('id');
    }

    // Mantiene la relación usada por las pantallas actuales, pero solo devuelve
    // el documento vigente. Así una anulación habilita la reemisión del lote.
    public function emision()
    {
        return $this->hasOne(CertificadoEmitido::class, 'id_certificado_registro')
            ->vigentes()
            ->latest('orden_emision')
            ->latest('id');
    }
}

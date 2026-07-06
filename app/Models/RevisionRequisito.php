<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RevisionRequisito extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'revisiones_requisitos';

    protected $fillable = [
        'id_requisito_certificado',
        'id_evidencia_requisito',
        'id_usuario_revisor',
        'resultado_cumple',
        'estado',
    ];

    // Relación muchos a uno (muchas revisiones pertenecen a un requisito certificado)
    public function requisitoCertificado()
    {
        return $this->belongsTo(RequisitoCertificado::class, 'id_requisito_certificado');
    }

    // Relación muchos a uno (muchas revisiones pertenecen a una evidencia)
    public function evidenciaRequisito()
    {
        return $this->belongsTo(EvidenciaRequisito::class, 'id_evidencia_requisito');
    }

    // Relación muchos a uno (muchas revisiones pertenecen a un usuario revisor)
    public function usuarioRevisor()
    {
        return $this->belongsTo(User::class, 'id_usuario_revisor');
    }

    // Relación uno a muchos (una revisión tiene muchas observaciones)
    public function observacionesRequisitos()
    {
        return $this->hasMany(ObservacionRequisito::class, 'id_revision_requisito');
    }
}

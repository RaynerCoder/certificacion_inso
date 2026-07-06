<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvidenciaRequisito extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'evidencias_requisitos';

    protected $fillable = [
        'id_requisito_certificado',
        'id_tipo_evidencia',
        'valor',
        'estado',
        'id_usuario_registro',
        'id_usuario_modificacion',
        'id_usuario_eliminacion',
    ];

    // Relación muchos a uno (muchas evidencias pertenecen a un requisito certificado)
    public function requisitoCertificado()
    {
        return $this->belongsTo(RequisitoCertificado::class, 'id_requisito_certificado');
    }

    // Relación muchos a uno (muchas evidencias pertenecen a un tipo de evidencia)
    public function tipoEvidencia()
    {
        return $this->belongsTo(TipoEvidencia::class, 'id_tipo_evidencia');
    }

    // Relación uno a muchos (una evidencia tiene muchas revisiones)
    public function revisionesRequisitos()
    {
        return $this->hasMany(RevisionRequisito::class, 'id_evidencia_requisito');
    }

    // Relación muchos a uno (muchas evidencias pertenecen a un usuario que registró)
    public function usuarioRegistro()
    {
        return $this->belongsTo(User::class, 'id_usuario_registro');
    }
}

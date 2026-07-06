<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequisitoTipoCertificado extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'requisitos_tipos_certificados';

    protected $fillable = [
        'id_requisito',
        'id_tipo_certificado',
        'id_tipo_evidencia',
        'estado',
    ];

    // Relación muchos a uno (muchos requisitos tipos certificados pertenecen a un requisito)
    public function requisito()
    {
        return $this->belongsTo(Requisito::class, 'id_requisito');
    }

    // Relación muchos a uno (muchos requisitos tipos certificados pertenecen a un tipo de certificado)
    public function tipoCertificado()
    {
        return $this->belongsTo(TipoCertificado::class, 'id_tipo_certificado');
    }

    // Relación muchos a uno (muchos requisitos tipos certificados pertenecen a un tipo de evidencia)
    public function tipoEvidencia()
    {
        return $this->belongsTo(TipoEvidencia::class, 'id_tipo_evidencia');
    }

    // Relación uno a muchos (un requisito tipo certificado tiene muchas dependencias)
    public function dependenciasRequisitos()
    {
        return $this->hasMany(DependenciaRequisito::class, 'id_requisito_tipo_certificado');
    }
}

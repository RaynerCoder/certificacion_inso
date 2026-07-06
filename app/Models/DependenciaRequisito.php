<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DependenciaRequisito extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'dependencias_requisitos';

    protected $fillable = [
        'id_requisito_tipo_certificado',
        'id_tipo_certificado_requerido',
        'estado',
    ];

    // Relación muchos a uno (muchas dependencias pertenecen a un requisito tipo certificado)
    public function requisitoTipoCertificado()
    {
        return $this->belongsTo(RequisitoTipoCertificado::class, 'id_requisito_tipo_certificado');
    }

    // Relación muchos a uno (muchas dependencias pertenecen a un tipo de certificado requerido)
    public function tipoCertificadoRequerido()
    {
        return $this->belongsTo(TipoCertificado::class, 'id_tipo_certificado_requerido');
    }
}

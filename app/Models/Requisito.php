<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Requisito extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'requisitos';

    protected $fillable = [
        'descripcion',
        'estado',
    ];

    // Relación uno a muchos (un requisito puede pertenecer a varios tipos de certificados)
    public function requisitoTiposCertificados()
    {
        return $this->hasMany(RequisitoTipoCertificado::class, 'id_requisito');
    }

    // Relación muchos a muchos (muchos requisitos pertenecen a muchos tipos de certificados)
    public function tiposCertificados()
    {
        return $this->belongsToMany(
            TipoCertificado::class,
            'requisitos_tipos_certificados',
            'id_requisito',
            'id_tipo_certificado'
        )
            ->withPivot('id', 'id_tipo_evidencia', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    // Relación uno a muchos (un requisito puede pertenecer a muchos certificados)
    public function requisitoCertificados()
    {
        return $this->hasMany(RequisitoCertificado::class, 'id_requisito');
    }

    // Relación muchos a muchos (muchos requisitos pertenecen a muchos certificados)
    public function certificados()
    {
        return $this->belongsToMany(
            Certificado::class,
            'requisitos_certificados',
            'id_requisito',
            'id_certificado'
        )
            ->withPivot('id', 'cumple', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}

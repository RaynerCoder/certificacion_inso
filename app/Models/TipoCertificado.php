<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoCertificado extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'tipos_certificados';

    protected $fillable = [
        'nombre',
        'id_area',
        'estado',
    ];

    // Relación muchos a uno (muchos tipos de certificados pertenecen a un área)
    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    // Relación uno a muchos (un tipo de certificado tiene muchos certificados)
    public function certificados()
    {
        return $this->hasMany(Certificado::class, 'id_tipo_certificado');
    }

    // Relación uno a muchos (un tipo de certificado tiene muchos requisitos tipos certificados)
    public function tipoCertificadoRequisitos()
    {
        return $this->hasMany(RequisitoTipoCertificado::class, 'id_tipo_certificado');
    }

    // Relación muchos a muchos (muchos tipos de certificados pertenecen a muchos requisitos)
    public function requisitos()
    {
        return $this->belongsToMany(
            Requisito::class,
            'requisitos_tipos_certificados',
            'id_tipo_certificado',
            'id_requisito'
        )
            ->withPivot('id', 'id_tipo_evidencia', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }

    // Relación uno a muchos (un tipo de certificado requerido tiene muchas dependencias)
    public function dependenciasDondeEsRequerido()
    {
        return $this->hasMany(DependenciaRequisito::class, 'id_tipo_certificado_requerido');
    }
}

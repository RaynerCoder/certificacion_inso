<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class RequisitoCertificado extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'requisitos_certificados';

    protected $fillable = [
        'id_certificado',
        'id_requisito',
        'cumple',
        'estado',
    ];

    // Relación muchos a uno (muchos requisitos certificados pertenecen a un certificado)
    public function certificado()
    {
        return $this->belongsTo(Certificado::class, 'id_certificado');
    }

    // Relación muchos a uno (muchos requisitos certificados pertenecen a un requisito)
    public function requisito()
    {
        return $this->belongsTo(Requisito::class, 'id_requisito');
    }

    // Relación uno a muchos (un requisito certificado tiene muchas evidencias)
    public function evidenciasRequisitos()
    {
        return $this->hasMany(EvidenciaRequisito::class, 'id_requisito_certificado');
    }

    // Relación uno a muchos (un requisito certificado tiene muchas revisiones)
    public function revisionesRequisitos()
    {
        return $this->hasMany(RevisionRequisito::class, 'id_requisito_certificado');
    }

}

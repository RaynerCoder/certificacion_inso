<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class TipoEvidencia extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'tipos_evidencias';

    protected $fillable = [
        'codigo',
        'nombre',
        'descripcion',
        'tamanio_maximo_mb',
        'estado',
    ];

    // Relación uno a muchos (un tipo de evidencia tiene muchos requisitos tipos certificados)
    public function requisitoTiposCertificados()
    {
        return $this->hasMany(RequisitoTipoCertificado::class, 'id_tipo_evidencia');
    }

    // Relación uno a muchos (un tipo de evidencia tiene muchas evidencias de requisitos)
    public function evidenciasRequisitos()
    {
        return $this->hasMany(EvidenciaRequisito::class, 'id_tipo_evidencia');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Empresa extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'empresas';
    protected $fillable = [
        'id_persona',
        'id_tipo_empresa',
        'razon_social',
        'matricula',
        'latitud',
        'longitud',
        'estado'
    ];

    // Relación uno a uno (una empresa se relaciona con una persona jurídica)
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }    

    // Relación muchos a uno (muchas empresas pertenecen a un tipo de empresa)
    public function tipoEmpresa()
    {
        return $this->belongsTo(TipoEmpresa::class, 'id_tipo_empresa');
    }

    // Relación uno a muchos (un empresa tiene varios responsables)
    public function responsables()
    {
        return $this->hasMany(Responsable::class, 'id_empresa');
    }
    
}

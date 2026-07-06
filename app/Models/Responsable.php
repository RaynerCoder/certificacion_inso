<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Responsable extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'responsables';
    protected $fillable = [
        'id_empresa',
        'id_persona',
        'id_rol',
        'url_respaldo',
        'fecha_registro',
        'fecha_baja',
        'estado'
    ];
    
     // Relación muchos a uno (muchos responsables pertenecen a una empresa)
    public function empresa()
    {
        return $this->belongsTo(Empresa::class, 'id_empresa');
    }

    // Relación muchos a uno (muchos responsables son persona)
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    // Relación muchos a uno (muchos responsables pertenecen a un rol)
    public function rol()
    {
        return $this->belongsTo(Role::class, 'id_rol');
    }
  
    
}

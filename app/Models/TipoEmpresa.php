<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class TipoEmpresa extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'tipos_empresas';
    protected $fillable = [
        'descripcion',
        'estado',
    ];


    // Relación uno a muchos (un tipo de empresa esta relacionado con muchas empresas)
    public function empresas()
    {
        return $this->hasMany(Empresa::class, 'id_tipo_empresa');
    }  

}

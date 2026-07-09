<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OcupacionCob extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'ocupaciones_cob';

    protected $fillable = [
        'codigo_gran_grupo',
        'descripcion_gran_grupo',
        'codigo_subgrupo_principal',
        'descripcion_subgrupo_principal',
        'codigo_subgrupo',
        'descripcion_subgrupo',
        'codigo_grupo_primario',
        'descripcion_grupo_primario',
        'codigo_ocupacion',
        'descripcion_ocupacion',
    ];

    // Relacion uno a muchos (una ocupacion puede estar asignada a muchas personas naturales)
    public function naturales()
    {
        return $this->hasMany(Natural::class, 'id_ocupacion');
    }
}

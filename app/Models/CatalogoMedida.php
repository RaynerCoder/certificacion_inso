<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CatalogoMedida extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'catalogos_medidas';

    protected $fillable = [
        'nombre',
        'abreviatura',
        'tipo',
        'estado',
    ];

    // Relación uno a muchos (una unidad puede usarse en muchas presentaciones)
    public function presentaciones()
    {
        return $this->hasMany(Presentacion::class, 'id_catalogo_unidad');
    }

    // Relación uno a muchos (una unidad puede usarse en muchos registros)
    public function registros()
    {
        return $this->hasMany(Registro::class, 'id_catalogo_unidad');
    }
}

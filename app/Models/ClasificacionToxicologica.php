<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClasificacionToxicologica extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'clasificaciones_toxicologicas';

    protected $fillable = [
        'descripcion',
        'codigo',
        'estado',
    ];

    // Productos asociados a esta clasificación toxicológica.
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_clasificacion_toxicologica');
    }
}

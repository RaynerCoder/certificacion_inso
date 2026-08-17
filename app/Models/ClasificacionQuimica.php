<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClasificacionQuimica extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'clasificaciones_quimicas';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    // Productos asociados a esta clasificación química.
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_clasificacion_quimica');
    }
}

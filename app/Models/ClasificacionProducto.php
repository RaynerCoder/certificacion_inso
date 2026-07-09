<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ClasificacionProducto extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'clasificaciones_productos';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    // Relación uno a muchos (una clasificación puede pertenecer a muchos productos)
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_clasificacion_producto');
    }
}

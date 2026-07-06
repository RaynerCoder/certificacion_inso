<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class TipoProducto extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'tipos_productos';
    protected $fillable = [
        'descripcion',
        'codigo',
        'estado',
    ];    

    // Relación uno a muchos (un tipo de producto esta relacionado con muchos productos)
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_tipo_producto');
    }     
     

}

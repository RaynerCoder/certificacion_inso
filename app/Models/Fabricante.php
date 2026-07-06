<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Fabricante extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'fabricantes';
    protected $fillable = [
        'nombre',
        'descripcion',
        'razon_social',
        'estado',
    ];

    // Relación uno a muchos (un fabricante tiene muchos productos)
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_fabricante');
    }    
    
    

}

<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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

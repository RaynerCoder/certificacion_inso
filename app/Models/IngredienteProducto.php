<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IngredienteProducto extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'ingredientes_productos';

    protected $fillable = [
        'id_ingrediente',
        'id_producto',
        'porcentaje',
        'estado',
    ];

    // Relacion muchos a uno (muchas asignaciones pertenecen a un ingrediente)
    public function ingrediente()
    {
        return $this->belongsTo(Ingrediente::class, 'id_ingrediente');
    }

    // Relacion muchos a uno (muchas asignaciones pertenecen a un producto)
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}

<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ingrediente extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'ingredientes';

    protected $fillable = [
        'nombre',
        'composicion',
        'riesgo_salud',
        'estado',
    ];

    // Relación uno a muchos (un ingrediente puede estar en muchos registros de productos)
    public function ingredienteProductos()
    {
        return $this->hasMany(IngredienteProducto::class, 'id_ingrediente');
    }

    // Relación muchos a muchos (muchos ingredientes pertenecen a muchos productos)
    public function productos()
    {
        return $this->belongsToMany(
            Producto::class,
            'ingredientes_productos',
            'id_ingrediente',
            'id_producto'
        )
            ->withPivot('id', 'porcentaje', 'estado')
            ->withTimestamps()
            ->wherePivotNull('deleted_at');
    }
}

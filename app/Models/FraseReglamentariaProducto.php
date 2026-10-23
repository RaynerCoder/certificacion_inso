<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class FraseReglamentariaProducto extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'frases_reglamentarias_productos';

    protected $fillable = [
        'id_frase_reglamentaria',
        'id_producto',
        'estado',
    ];

    // Relación muchos a uno: la asignación pertenece a una frase reglamentaria.
    public function fraseReglamentaria()
    {
        return $this->belongsTo(FraseReglamentaria::class, 'id_frase_reglamentaria');
    }

    // Relación muchos a uno: la asignación pertenece a un producto.
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}

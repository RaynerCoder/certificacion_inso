<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Presentacion extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'presentaciones';

    protected $fillable = [
        'id_producto',
        'url_etiqueta',
        'cantidad',
        'unidad',
        'descripcion',
        'estado',
    ];

    // Relación muchos a uno (muchas presentaciones pertenecen a un producto)
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }

    // Relación uno a muchos (una presentación tiene muchos registros)
    public function registros()
    {
        return $this->hasMany(Registro::class, 'id_presentacion');
    }
}

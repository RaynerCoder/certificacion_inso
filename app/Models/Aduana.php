<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Aduana extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'aduanas';

    protected $fillable = [
        'codigo_cotizacion',
        'index_solicitud',
        'codigo_solicitud',
        'nombre_operativo',
        'acta_int',
        'item',
        'caracteristica',
        'marca',
        'vencimiento',
        'unidad',
        'medida',
        'peso',
        'observacion',
        'id_producto',
        'estado',
    ];

    // Relación muchos a uno (muchas aduanas pertenecen a un producto)
    public function producto()
    {
        return $this->belongsTo(Producto::class, 'id_producto');
    }
}

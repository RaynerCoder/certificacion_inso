<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantillaColumna extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'plantillas_columnas';

    protected $fillable = [
        'id_plantilla_elemento',
        'codigo_campo',
        'titulo_columna',
        'ancho',
        'orden',
        'estado',
    ];

    // Relación muchos a uno (muchas columnas pertenecen a un elemento tipo tabla)
    public function plantillaElemento()
    {
        return $this->belongsTo(PlantillaElemento::class, 'id_plantilla_elemento');
    }
}

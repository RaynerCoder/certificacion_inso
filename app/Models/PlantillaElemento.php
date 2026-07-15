<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantillaElemento extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'plantillas_elementos';

    protected $fillable = [
        'id_plantilla_certificado',
        'tipo_elemento',
        'codigo_elemento',
        'texto_fijo',
        'pagina',
        'posicion_x',
        'posicion_y',
        'ancho',
        'alto',
        'tamano_letra',
        'alineacion',
        'padding_x',
        'padding_y',
        'interlineado',
        'negrita',
        'cursiva',
        'subrayado',
        'color_texto',
        'tipo_letra',
        'z_index',
        'orden',
        'estado',
    ];

    // Relación muchos a uno (muchos elementos pertenecen a una plantilla)
    public function plantillaCertificado()
    {
        return $this->belongsTo(PlantillaCertificado::class, 'id_plantilla_certificado');
    }

    // Relación uno a muchos (un elemento tipo tabla tiene muchas columnas)
    public function columnas()
    {
        return $this->hasMany(PlantillaColumna::class, 'id_plantilla_elemento')
            ->orderBy('orden');
    }
}

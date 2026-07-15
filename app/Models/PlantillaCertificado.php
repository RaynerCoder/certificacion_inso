<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PlantillaCertificado extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'plantillas_certificados';

    protected $fillable = [
        'id_tipo_certificado',
        'nombre',
        'descripcion',
        'tamano_papel',
        'orientacion',
        'ancho_lienzo_px',
        'alto_lienzo_px',
        'url_fondo',
        'ajuste_fondo',
        'fondo_trabajo',
        'imprimir_transparente',
        'estado',
    ];

    // Relación muchos a uno (muchas plantillas pertenecen a un tipo de certificado)
    public function tipoCertificado()
    {
        return $this->belongsTo(TipoCertificado::class, 'id_tipo_certificado');
    }

    // Relación uno a muchos (una plantilla tiene muchos elementos)
    public function elementos()
    {
        return $this->hasMany(PlantillaElemento::class, 'id_plantilla_certificado')
            ->orderBy('orden');
    }

    // Relación uno a muchos (una plantilla tiene muchos elementos activos)
    public function elementosActivos()
    {
        return $this->hasMany(PlantillaElemento::class, 'id_plantilla_certificado')
            ->where('estado', 'ACTIVO')
            ->orderBy('orden');
    }
}

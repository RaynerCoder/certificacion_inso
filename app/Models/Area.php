<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Area extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'areas';

    protected $fillable = [
        'id_area_padre',
        'nombre',
        'descripcion',
        'estado',
    ];

    // Area superior dentro de la estructura institucional.
    public function areaPadre()
    {
        return $this->belongsTo(Area::class, 'id_area_padre');
    }

    // Subareas que dependen directamente de esta area.
    public function subareas()
    {
        return $this->hasMany(Area::class, 'id_area_padre');
    }

    // Cargos administrativos o tecnicos asociados a esta area.
    public function cargos()
    {
        return $this->hasMany(Cargo::class, 'id_area');
    }

    // Tipos de certificados que se atienden por defecto desde esta area.
    public function tiposCertificados()
    {
        return $this->hasMany(TipoCertificado::class, 'id_area');
    }

}

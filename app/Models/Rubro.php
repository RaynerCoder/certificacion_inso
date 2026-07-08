<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Rubro extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'rubros';

    protected $fillable = [
        'nombre',
        'descripcion',
        'estado',
    ];

    // Relacion muchos a muchos (un rubro puede pertenecer a varias personas)
    public function personas()
    {
        return $this->belongsToMany(Persona::class, 'personas_rubros', 'id_rubro', 'id_persona')
            ->withPivot('estado');
    }
}

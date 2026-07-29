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
        'id_rubro_padre',
        'codigo_caeb',
        'nivel_caeb',
        'nombre',
        'estado',
    ];

    // Nivel CAEB inmediatamente superior.
    public function padre()
    {
        return $this->belongsTo(self::class, 'id_rubro_padre');
    }

    // Niveles CAEB que dependen directamente de este rubro.
    public function hijos()
    {
        return $this->hasMany(self::class, 'id_rubro_padre');
    }

    // Relacion muchos a muchos (un rubro puede pertenecer a varias personas)
    public function personas()
    {
        return $this->belongsToMany(Persona::class, 'personas_rubros', 'id_rubro', 'id_persona')
            ->withPivot('estado');
    }
}

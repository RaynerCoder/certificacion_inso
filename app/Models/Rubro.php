<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Rubro extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'rubros';
    protected $fillable = [
        'id_persona',
        'nombre',
        'estado'
    ];

    // Relación muchos a uno (muchos rubros tiene una persona)
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

}

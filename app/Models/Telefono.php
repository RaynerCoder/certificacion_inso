<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Telefono extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'telefonos';
    protected $fillable = [
        'id_persona',
        'numero',
        'estado'
    ];

    // Relación muchos a uno (muchos telefonos pertenecen a una persona)
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }
}

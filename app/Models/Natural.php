<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Natural extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'naturals';
    protected $fillable = [
        'id_persona',
        'ci',
        'complemento',
        'expedido',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'apellido_casado',
        'fecha_nacimiento',
        'genero',
        'ocupacion',
    ];

    // Relación uno a uno (un natural pertenece a una persona)
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

 
}


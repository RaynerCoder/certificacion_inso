<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Natural extends Model
{
    use SoftDeletes, Auditable;

    public const EXPEDIDOS = [
        'LP' => 'La Paz',
        'CB' => 'Cochabamba',
        'SC' => 'Santa Cruz',
        'OR' => 'Oruro',
        'PT' => 'Potosi',
        'CH' => 'Chuquisaca',
        'TJ' => 'Tarija',
        'BN' => 'Beni',
        'PD' => 'Pando',
        'EX' => 'Extranjero',
    ];

    protected $table = 'naturals';

    protected $fillable = [
        'id_persona',
        'id_ocupacion',
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

    // Relacion uno a uno inversa (un natural pertenece a una persona)
    public function persona()
    {
        return $this->belongsTo(Persona::class, 'id_persona');
    }

    // Relacion muchos a uno (varias personas naturales pueden compartir una ocupacion COB)
    public function ocupacionCob()
    {
        return $this->belongsTo(OcupacionCob::class, 'id_ocupacion');
    }
}

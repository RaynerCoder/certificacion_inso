<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Ambito extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'ambitos';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    // Relacion uno a muchos (un ambito puede tener varios territorios)
    public function territorios()
    {
        return $this->hasMany(Territorio::class, 'id_ambito');
    }
}

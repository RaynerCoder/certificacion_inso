<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\Auditable;

class Territorio extends Model
{
    use SoftDeletes, Auditable;
    protected $table = 'territorios';
    protected $fillable = [
        'id_ambito',
        'id_padre_territorio',
        'nombre',
        'codigo',
        'estado',
    ];

    // Relacion muchos a uno (un territorio pertenece a un ambito)
    public function ambito()
    {
        return $this->belongsTo(Ambito::class, 'id_ambito');
    }

    // Relación uno a muchos (un territorio tiene muchos productos)
    public function productos()
    {
        return $this->hasMany(Producto::class, 'id_territorio_pais');
    }     
    
    // Relacion de muchos a uno (muchos territorios pertenecen a un territorio padre)
    public function territorioPadre()
    {
        return $this->belongsTo(Territorio::class, 'id_padre_territorio');
    }

    // Relacion de uno a muchos (un territorio tiene muchos territorios hijos)
    public function territoriosHijos()
    {
        return $this->hasMany(Territorio::class, 'id_padre_territorio');
    }    

}

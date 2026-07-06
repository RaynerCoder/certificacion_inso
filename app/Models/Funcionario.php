<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Funcionario extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'funcionarios';

    protected $fillable = [
        'id_usuario',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'carnet',
        'telefono',
        'genero',
        'estado',
    ];

    // Relacion uno a uno: la ficha laboral pertenece a una cuenta de usuario.
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario');
    }

    // Relacion muchos a muchos: un funcionario puede tener varios cargos.
    public function cargos()
    {
        return $this->belongsToMany(Cargo::class, 'funcionarios_cargos', 'id_funcionario', 'id_cargo')
            ->withPivot('id')
            ->withTimestamps();
    }

    // Relacion directa con la tabla pivote para consultas administrativas.
    public function funcionariosCargos()
    {
        return $this->hasMany(FuncionarioCargo::class, 'id_funcionario');
    }
}

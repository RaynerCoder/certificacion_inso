<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class Cargo extends Model
{

    use SoftDeletes, Auditable;

    protected $table = 'cargos';

    protected $fillable = [
        'id_area',
        'nombre',
        'descripcion',
        'estado',
    ];

    // Area institucional a la que pertenece el cargo.
    public function area()
    {
        return $this->belongsTo(Area::class, 'id_area');
    }

    // Relacion muchos a muchos: un cargo puede estar asignado a varios funcionarios.
    public function funcionarios()
    {
        return $this->belongsToMany(Funcionario::class, 'funcionarios_cargos', 'id_cargo', 'id_funcionario')
            ->withPivot('id')
            ->withTimestamps();
    }

    // Relacion directa con la tabla pivote para revisar asignaciones.
    public function funcionariosCargos()
    {
        return $this->hasMany(FuncionarioCargo::class, 'id_cargo');
    }
}

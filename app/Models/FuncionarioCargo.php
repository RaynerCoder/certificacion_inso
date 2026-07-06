<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;

class FuncionarioCargo extends Model
{
    use SoftDeletes, Auditable;
    
    protected $table = 'funcionarios_cargos';

    protected $fillable = [
        'id_funcionario',
        'id_cargo',
    ];

    // Funcionario que recibe el cargo.
    public function funcionario()
    {
        return $this->belongsTo(Funcionario::class, 'id_funcionario');
    }

    // Cargo asignado al funcionario.
    public function cargo()
    {
        return $this->belongsTo(Cargo::class, 'id_cargo');
    }
}

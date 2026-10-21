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
        'ci',
        'complemento',
        'telefono',
        'genero',
    ];

    public function setNombresAttribute(?string $valor): void
    {
        $this->attributes['nombres'] = $this->textoEnMayusculas($valor);
    }

    public function setApellidoPaternoAttribute(?string $valor): void
    {
        $this->attributes['apellido_paterno'] = $this->textoEnMayusculas($valor);
    }

    public function setApellidoMaternoAttribute(?string $valor): void
    {
        $this->attributes['apellido_materno'] = $this->textoEnMayusculas($valor);
    }

    public function setComplementoAttribute(?string $valor): void
    {
        $this->attributes['complemento'] = mb_strtoupper(trim((string) $valor), 'UTF-8');
    }

    private function textoEnMayusculas(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $valor = trim(preg_replace('/\s+/', ' ', $valor));

        return $valor === '' ? null : mb_strtoupper($valor, 'UTF-8');
    }

    // Relación uno a uno: estos datos personales pertenecen a una cuenta de usuario.
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

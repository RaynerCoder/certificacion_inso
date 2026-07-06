<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Role extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'roles';

    protected $fillable = [
        'name',
        'slug',
        'descripcion',
        'especial',
        'estado',
    ];

    // Relacion muchos a muchos (muchos roles pertenecen a muchos usuarios)
    public function users()
    {
        return $this->belongsToMany(User::class, 'roles_users', 'id_role', 'id_user')
            ->withPivot('id')
            ->withTimestamps();
    }

    // Relacion uno a muchos (un rol tiene muchas asignaciones de permisos)
    public function rolPermisos()
    {
        return $this->hasMany(PermisoRole::class, 'id_role');
    }

    // Relacion muchos a muchos (muchos roles tienen muchos permisos)
    public function permisos()
    {
        return $this->belongsToMany(Permiso::class, 'permisos_roles', 'id_role', 'id_permiso')
            ->withPivot('id')
            ->withTimestamps();
    }

    // Relacion uno a muchos (un rol puede estar asignado a muchos responsables de empresa)
    public function responsables()
    {
        return $this->hasMany(Responsable::class, 'id_rol');
    }
}


/*
belongsToMany(
    ModeloRelacionado,
    tabla_pivote,
    fk_modelo_actual,
    fk_modelo_relacionado
)
*/

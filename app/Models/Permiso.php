<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Permiso extends Model
{
    use SoftDeletes, Auditable;

    protected $table = 'permisos';

    protected $fillable = [
        'nombre',
        'estado',
    ];

    // Relacion uno a muchos (un permiso tiene muchas asignaciones a roles)
    public function permisoRoles()
    {
        return $this->hasMany(PermisoRole::class, 'id_permiso');
    }

    // Relacion muchos a muchos (muchos permisos pertenecen a muchos roles)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'permisos_roles', 'id_permiso', 'id_role')
            ->withPivot('id')
            ->withTimestamps();
    }

    // Relacion uno a muchos (un permiso tiene muchas asignaciones directas a usuarios)
    public function permisoUsers()
    {
        return $this->hasMany(PermisoUser::class, 'id_permiso');
    }

    // Relacion muchos a muchos (muchos permisos pertenecen directamente a muchos usuarios)
    public function users()
    {
        return $this->belongsToMany(User::class, 'permisos_users', 'id_permiso', 'id_user')
            ->withPivot('id')
            ->withTimestamps();
    }
}

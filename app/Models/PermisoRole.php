<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PermisoRole extends Model
{
    use Auditable;

    protected $table = 'permisos_roles';

    protected $fillable = [
        'id_permiso',
        'id_role',
    ];

    // Relacion muchos a uno (muchas asignaciones pertenecen a un permiso)
    public function permiso()
    {
        return $this->belongsTo(Permiso::class, 'id_permiso');
    }

    // Relacion muchos a uno (muchas asignaciones pertenecen a un rol)
    public function rol()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }
}

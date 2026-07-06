<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class PermisoUser extends Model
{
    use Auditable;

    protected $table = 'permisos_users';

    protected $fillable = [
        'id_user',
        'id_permiso',
    ];

    // Relacion muchos a uno (muchas asignaciones pertenecen a un usuario)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    // Relacion muchos a uno (muchas asignaciones pertenecen a un permiso)
    public function permiso()
    {
        return $this->belongsTo(Permiso::class, 'id_permiso');
    }
}

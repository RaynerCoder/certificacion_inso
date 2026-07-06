<?php

namespace App\Models;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    use Auditable;

    protected $table = 'roles_users';

    protected $fillable = [
        'id_role',
        'id_user',
    ];

    // Relacion muchos a uno (muchas asignaciones pertenecen a un rol)
    public function role()
    {
        return $this->belongsTo(Role::class, 'id_role');
    }

    // Relacion muchos a uno (muchas asignaciones pertenecen a un usuario)
    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;
use App\Traits\Auditable;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;
    use HasProfilePhoto;
    use Notifiable;
    // SoftDeletes mantiene la cuenta en historial y solo llena deleted_at al eliminar.
    use SoftDeletes;
    use TwoFactorAuthenticatable;
    

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'estado'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // Relacion muchos a muchos (muchos usuarios tienen muchos roles)
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'roles_users', 'id_user', 'id_role')
            ->withPivot('id')
            ->withTimestamps();
    }

    // Relacion uno a muchos (un usuario tiene muchas asignaciones de roles)
    public function rolesUsers()
    {
        return $this->hasMany(RoleUser::class, 'id_user');
    }

    // Relacion uno a uno (cuenta propia asignada a una persona o empresa)
    public function persona()
    {
        return $this->hasOne(Persona::class, 'id_usuario');
    }

    // Relacion uno a uno: cuenta interna vinculada a su ficha de funcionario.
    public function funcionario()
    {
        return $this->hasOne(Funcionario::class, 'id_usuario');
    }

    // Relacion muchos a muchos (muchos usuarios tienen permisos directos)
    public function permisosDirectos()
    {
        return $this->belongsToMany(Permiso::class, 'permisos_users', 'id_user', 'id_permiso')
            ->withPivot('id')
            ->withTimestamps();
    }

    // Relacion uno a muchos (un usuario tiene muchas asignaciones directas de permisos)
    public function permisosUsers()
    {
        return $this->hasMany(PermisoUser::class, 'id_user');
    }

    // Relacion uno a muchos usada por la bandeja de solicitudes.
    // Permite calcular cuantos tramites tiene asignados un tecnico.
    public function tramiteSeguimientosAsignados()
    {
        return $this->hasMany(Seguimiento::class, 'id_usuario_siguiente');
    }

    // Verifica si el usuario tiene un rol por su slug.
    public function tieneRol(string $slug): bool
    {
        $rolesConsultados = $slug === 'administrador'
            ? ['administrador', 'super-administrador']
            : [$slug];

        return $this->roles()
            ->whereIn('slug', $rolesConsultados)
            ->where('roles.estado', 1)
            ->exists();
    }

    // La cuenta inicial del sistema no puede eliminarse desde la administracion de usuarios.
    public function esSuperAdministrador(): bool
    {
        return mb_strtolower((string) $this->email) === 'super.admin@gmail.com'
            || $this->tieneRol('super-administrador');
    }

    // Punto central para validar permisos dinamicos del sistema.
    // Acepta un permiso o varios; si uno coincide, permite la accion.
    public function puede(string|array $permisos): bool
    {
        if ($this->esSuperAdministrador() || $this->tieneRol('administrador')) {
            return true;
        }

        foreach ((array) $permisos as $permiso) {
            if ($this->tienePermiso($permiso)) {
                return true;
            }
        }

        return false;
    }

    // Verifica permisos directos y permisos heredados por roles.
    // Se mantiene como metodo base porque ya consulta tus tablas pivote.
    public function tienePermiso(string $permiso): bool
    {
        $tienePermisoDirecto = $this->permisosDirectos()
            ->where('nombre', $permiso)
            ->where('permisos.estado', 1)
            ->exists();

        if ($tienePermisoDirecto) {
            return true;
        }

        return $this->roles()
            ->where('roles.estado', 1)
            ->whereHas('permisos', function ($query) use ($permiso) {
                $query->where('nombre', $permiso)
                    ->where('permisos.estado', 1);
            })
            ->exists();
    }
}

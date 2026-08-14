<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens;

    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory;

    use HasProfilePhoto;
    use Notifiable;
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
        'estado',
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

    // Persona natural propietaria de la cuenta.
    public function persona()
    {
        return $this->hasOne(Persona::class, 'id_usuario');
    }

    // Relaciones activas que permiten actuar por una empresa desde esta cuenta.
    public function relacionesEmpresarialesActivas(): Collection
    {
        $persona = $this->persona;

        if (! $persona || strtoupper((string) $persona->estado) !== 'ACTIVO') {
            return collect();
        }

        return Responsable::query()
            ->with(['empresa.persona', 'rol'])
            ->where('id_persona', $persona->id)
            ->whereIn('estado', ['1', 'ACTIVO'])
            ->whereHas('empresa', fn ($empresa) => $empresa
                ->whereIn('estado', ['1', 'ACTIVO'])
                ->whereHas('persona', fn ($empresaPersona) => $empresaPersona
                    ->whereIn('estado', ['1', 'ACTIVO'])))
            ->whereHas('rol', fn ($rol) => $rol
                ->where('estado', 1)
                ->whereIn('slug', ['solicitante', 'tramitador']))
            ->get();
    }

    /**
     * Relaciones que esta cuenta puede utilizar al iniciar tramites.
     * Un representante legal trabaja con una sola empresa; una cuenta exclusivamente
     * tramitadora puede operar con todas las empresas que la autorizaron.
     */
    public function relacionesEmpresarialesParaTramites(): Collection
    {
        $relaciones = $this->relacionesEmpresarialesActivas();
        $representacionPrincipal = $relaciones
            ->filter(fn (Responsable $relacion) => $relacion->rol?->slug === 'solicitante')
            ->sortByDesc('id')
            ->take(1)
            ->values();

        if ($representacionPrincipal->isNotEmpty()) {
            return $representacionPrincipal;
        }

        return $relaciones
            ->filter(fn (Responsable $relacion) => $relacion->rol?->slug === 'tramitador')
            ->sortBy(fn (Responsable $relacion) => $relacion->empresa?->razon_social)
            ->values();
    }

    public function empresasRepresentadasActivas(): Collection
    {
        return $this->relacionesEmpresarialesParaTramites()
            ->filter(fn (Responsable $relacion) => $relacion->rol?->slug === 'solicitante')
            ->pluck('empresa')
            ->filter()
            ->unique('id')
            ->sortBy('razon_social')
            ->values();
    }

    public function empresaRepresentadaActiva(): ?Empresa
    {
        return $this->empresasRepresentadasActivas()->first();
    }

    public function empresaDeAccesoActiva(): ?Empresa
    {
        $empresaRepresentada = $this->empresaRepresentadaActiva();

        if ($empresaRepresentada) {
            return $empresaRepresentada;
        }

        // Mantiene operativas las cuentas empresariales creadas antes del cambio de modelo.
        $empresaAnterior = $this->persona?->empresa;

        return $empresaAnterior
            && strtoupper((string) $this->persona?->estado) === 'ACTIVO'
            && strtoupper((string) $empresaAnterior->estado) === 'ACTIVO'
            ? $empresaAnterior
            : null;
    }

    // Relacion uno a uno: cuenta interna vinculada a su ficha de funcionario.
    public function funcionario()
    {
        return $this->hasOne(Funcionario::class, 'id_usuario');
    }

    /**
     * Nombre legible de la persona o entidad propietaria de la cuenta.
     */
    public function nombreCompleto(): string
    {
        $this->loadMissing(['funcionario', 'persona.natural', 'persona.empresa']);

        $nombreFuncionario = collect([
            $this->funcionario?->nombres,
            $this->funcionario?->apellido_paterno,
            $this->funcionario?->apellido_materno,
        ])->filter(fn ($parte) => filled($parte))->implode(' ');

        if ($nombreFuncionario !== '') {
            return $nombreFuncionario;
        }

        $nombreNatural = collect([
            $this->persona?->natural?->nombres,
            $this->persona?->natural?->apellido_paterno,
            $this->persona?->natural?->apellido_materno,
        ])->filter(fn ($parte) => filled($parte))->implode(' ');

        if ($nombreNatural !== '') {
            return $nombreNatural;
        }

        return $this->persona?->empresa?->razon_social ?: $this->name;
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
        if ($this->esSuperAdministrador()) {
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

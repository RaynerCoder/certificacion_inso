<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class UsuarioTable extends DataTableComponent
{
    protected $model = User::class;

    /**
     * Configura la tabla de usuarios: clave primaria y orden inicial.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Consulta usuarios con roles y permisos directos para evitar consultas repetidas.
     */
    public function builder(): Builder
    {
        return User::query()
            ->with([
                'roles',
                'permisosDirectos',
                'funcionario.cargos',
                'persona.natural',
                'persona.responsabilidadesEmpresariales.rol',
            ]);
    }

    /**
     * Define las columnas visibles del listado de usuarios.
     */
    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable()
                ->searchable(),

            Column::make("Correo", "email")
                ->sortable()
                ->searchable(),

            Column::make("Funcionario / Persona natural")
                ->label(fn ($fila) => $this->nombreFuncionarioONatural($fila))
                ->html(),

            Column::make("Número de carnet")
                ->label(fn ($fila) => $this->ciFuncionarioONatural($fila)),

            Column::make("Cargos")
                ->label(fn ($fila) => view('seguridad.chips-tabla', [
                    'items' => $fila->funcionario?->cargos ?? collect(),
                    'campo' => 'nombre',
                    'vacio' => 'Sin cargos',
                ])),

            Column::make("Roles")
                ->label(fn ($fila) => view('seguridad.chips-tabla', [
                    'items' => $fila->roles,
                    'campo' => 'name',
                    'vacio' => 'Sin roles',
                ])),

            Column::make("Permisos directos")
                ->label(fn ($fila) => view('seguridad.chips-tabla', [
                    'items' => $fila->permisosDirectos,
                    'campo' => 'nombre',
                    'vacio' => 'Sin permisos directos',
                ])),

            Column::make("Estado", "estado")
                ->format(fn ($estado) => view('tablas.chip_estado', [
                    'estado' => $estado,
                ]))
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('usuarios.accion', ['usuario' => $fila]);
                }),
        ];
    }

    /**
     * Muestra el nombre del funcionario o de la persona natural vinculada al usuario.
     */
    private function nombreFuncionarioONatural(User $usuario): string
    {
        $tiposAdministrativos = $usuario->roles
            ->map(fn ($rol) => match ($rol->slug) {
                'administrador' => 'Administrador',
                'super-administrador' => 'Super administrador',
                default => null,
            })
            ->filter();

        if ($usuario->funcionario) {
            $nombre = collect([
                $usuario->funcionario->nombres,
                $usuario->funcionario->apellido_paterno,
                $usuario->funcionario->apellido_materno,
            ])->filter()->join(' ');
            $tipos = collect(['Funcionario'])
                ->merge($tiposAdministrativos)
                ->unique()
                ->implode(' · ');

            return '<div class="leading-tight"><div>'.e($nombre).'</div><div class="text-[11px] leading-none text-emerald-600">'.e($tipos).'</div></div>';
        }

        if ($usuario->persona?->natural) {
            $nombre = collect([
                $usuario->persona->natural->nombres,
                $usuario->persona->natural->apellido_paterno,
                $usuario->persona->natural->apellido_materno,
            ])->filter()->join(' ');

            $tipos = collect(['Persona natural'])
                ->merge(
                    $usuario->persona->responsabilidadesEmpresariales
                        ->filter(fn ($responsabilidad) => in_array((string) $responsabilidad->estado, ['1', 'ACTIVO'], true))
                        ->map(fn ($responsabilidad) => match ($responsabilidad->rol?->slug) {
                            'solicitante' => 'Representante legal',
                            'tramitador' => 'Tramitador',
                            default => null,
                        })
                        ->filter()
                )
                ->merge($tiposAdministrativos)
                ->unique()
                ->implode(' · ');

            return '<div class="leading-tight"><div>'.e($nombre).'</div><div class="text-[11px] leading-none text-emerald-600">'.e($tipos).'</div></div>';
        }

        if ($tiposAdministrativos->isNotEmpty()) {
            $nombre = $usuario->name ?: $usuario->email;

            return '<div class="leading-tight"><div>'.e($nombre).'</div><div class="text-[11px] leading-none text-emerald-600">'.e($tiposAdministrativos->unique()->implode(' · ')).'</div></div>';
        }

        return '<span class="text-slate-500">Sin persona asociada</span>';
    }

    /**
     * Obtiene el documento de identidad del funcionario o de la persona natural.
     */
    private function ciFuncionarioONatural(User $usuario): string
    {
        if ($usuario->funcionario) {
            return $usuario->funcionario->ci
                ? $usuario->funcionario->ci.($usuario->funcionario->complemento ? '-'.$usuario->funcionario->complemento : '')
                : '-';
        }

        if ($usuario->persona?->natural) {
            $natural = $usuario->persona->natural;

            return trim((string) $natural->ci).($natural->complemento ? '-'.$natural->complemento : '');
        }

        return '-';
    }

}

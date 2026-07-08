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
            ->with(['roles', 'permisosDirectos', 'funcionario.cargos']);
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

            Column::make("Usuario", "name")
                ->sortable()
                ->searchable(),

            Column::make("Correo", "email")
                ->sortable()
                ->searchable(),

            Column::make("Funcionario")
                ->label(fn ($fila) => $this->nombreFuncionario($fila)),

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
                ->format(fn ($valor) => $this->estadoLiteral($valor))
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('usuarios.accion', ['usuario' => $fila]);
                }),
        ];
    }

    /**
     * Convierte el estado numerico de la base de datos a texto legible.
     */
    private function estadoLiteral($estado): string
    {
        return (string) $estado === '1' ? 'Activo' : 'Inactivo';
    }

    /**
     * Muestra el nombre completo de la ficha de funcionario vinculada al usuario.
     */
    private function nombreFuncionario(User $usuario): string
    {
        if (!$usuario->funcionario) {
            return 'No es funcionario';
        }

        return collect([
            $usuario->funcionario->nombres,
            $usuario->funcionario->apellido_paterno,
            $usuario->funcionario->apellido_materno,
        ])->filter()->join(' ');
    }

}

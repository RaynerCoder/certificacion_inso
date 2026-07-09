<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;

class RolTable extends DataTableComponent
{
    protected $model = Role::class;

    /**
     * Configura la tabla de roles: clave primaria y orden inicial.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Consulta roles con permisos y usuarios relacionados para el listado.
     */
    public function builder(): Builder
    {
        return Role::query()
            ->with(['permisos', 'users']);
    }

    /**
     * Define las columnas visibles del listado de roles.
     */
    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable()
                ->searchable(),

            Column::make("Rol", "name")
                ->sortable()
                ->searchable(),

            Column::make("Slug", "slug")
                ->sortable()
                ->searchable(),

            Column::make("Descripcion", "descripcion")
                ->sortable(),

            Column::make("Permisos")
                ->label(fn ($fila) => view('seguridad.chips-tabla', [
                    'items' => $fila->permisos,
                    'campo' => 'nombre',
                    'vacio' => 'Sin permisos',
                    'limite' => 8,
                    'tituloModal' => 'Permisos del rol ' . $fila->name,
                ])),

            Column::make("Especial", "especial")
                ->format(fn ($valor) => $valor ?: 'Normal')
                ->sortable(),

            Column::make("Estado", "estado")
                ->format(fn ($valor) => $this->estadoLiteral($valor))
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('roles.accion', ['rol' => $fila]);
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
}

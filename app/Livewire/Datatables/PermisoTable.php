<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Permiso;
use Illuminate\Database\Eloquent\Builder;

class PermisoTable extends DataTableComponent
{
    protected $model = Permiso::class;

    /**
     * Configura la tabla de permisos: clave primaria y orden inicial.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Consulta permisos con roles y usuarios relacionados para el listado.
     */
    public function builder(): Builder
    {
        return Permiso::query()
            ->with(['roles', 'users']);
    }

    /**
     * Define las columnas visibles del listado de permisos.
     */
    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable()
                ->searchable(),

            Column::make("Nombre", "nombre")
                ->sortable()
                ->searchable(),

            Column::make("Roles")
                ->label(fn ($fila) => view('seguridad.chips-tabla', [
                    'items' => $fila->roles,
                    'campo' => 'name',
                    'vacio' => 'Sin roles',
                ])),

            Column::make("Estado", "estado")
                ->format(fn ($valor) => $this->estadoLiteral($valor))
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('permisos.accion', ['permiso' => $fila]);
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

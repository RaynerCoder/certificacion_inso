<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Responsable;
use Illuminate\Database\Eloquent\Builder;

class ResponsableTable extends DataTableComponent
{
    protected $model = Responsable::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        // Muestra primero las asignaciones mas recientes.
        $this->setDefaultSort('id', 'desc');
    }

    public function builder(): Builder
    {
        return Responsable::query()
            ->select('responsables.*')
            // Calcula el nombre completo para ordenar y buscar sin recargar la tabla.
            ->selectRaw("TRIM(CONCAT_WS(' ', naturals.nombres, naturals.apellido_paterno, naturals.apellido_materno)) as nombre_persona")
            // Trae el nombre del rol relacionado sin guardar texto duplicado en responsables.
            ->selectRaw("roles.name as nombre_rol")
            ->leftJoin('personas', 'responsables.id_persona', '=', 'personas.id')
            ->leftJoin('naturals', 'personas.id', '=', 'naturals.id_persona')
            ->leftJoin('roles', 'responsables.id_rol', '=', 'roles.id')
            ->with([
                'empresa',
                'persona.natural',
                'rol',
            ]);
    }    

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),

            Column::make("Empresa", "empresa.razon_social")
                ->sortable()
                ->searchable(),

            Column::make("Nombre de la Persona", "id_persona")
                // Usa id_persona como columna real y muestra el alias nombre_persona calculado en builder().
                ->format(function ($valorIdPersona, Responsable $fila) {
                    return filled($fila->nombre_persona) ? $fila->nombre_persona : 'Sin nombre';
                })
                ->sortable(function (Builder $query, string $direction) {
                    return $query
                        ->orderBy('naturals.nombres', $direction)
                        ->orderBy('naturals.apellido_paterno', $direction)
                        ->orderBy('naturals.apellido_materno', $direction);
                })
                ->searchable(function (Builder $query, string $searchTerm) {
                    // Permite buscar por nombres o apellidos del responsable.
                    $query->orWhere('naturals.nombres', 'like', "%{$searchTerm}%")
                        ->orWhere('naturals.apellido_paterno', 'like', "%{$searchTerm}%")
                        ->orWhere('naturals.apellido_materno', 'like', "%{$searchTerm}%")
                        ->orWhere('naturals.ci', 'like', "%{$searchTerm}%");
                }),

            Column::make("Rol", "id_rol")
                // Muestra el nombre del rol relacionado, no el ID.
                ->format(fn ($valorIdRol, Responsable $fila) => $fila->nombre_rol ?: 'Sin rol')
                ->sortable(function (Builder $query, string $direction) {
                    return $query->orderBy('roles.name', $direction);
                })
                ->searchable(function (Builder $query, string $searchTerm) {
                    $query->orWhere('roles.name', 'like', "%{$searchTerm}%");
                }),

            Column::make("Fecha registro", "fecha_registro")
                ->format(function ($valor) {
                    return $valor ?: 'Sin fecha';
                })
                ->sortable(),

            Column::make("Estado", "estado")
                ->sortable(),

            Column::make('Acciones')
                ->label(function ($fila) {
                    return view('responsables.accion', ['responsable' => $fila]);
                }),                
        ];
    }
}

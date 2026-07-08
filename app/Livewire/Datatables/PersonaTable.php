<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;

class PersonaTable extends DataTableComponent
{
    protected $model = Persona::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        // Orden principal de la tabla: muestra primero las personas/empresas registradas recientemente.
        $this->setDefaultSort('id', 'desc');
    }

    public function builder(): Builder
    {
        return Persona::query()
            ->with([
                'natural',
                'territorio',
                'empresa.persona',
                'empresa.tipoEmpresa',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable(),

            Column::make("Tipo")
                ->label(function ($fila) {
                    return $fila->empresa ? 'Empresa' : 'Persona Natural';
                }),

            Column::make("Nombre / Razon social")
                ->label(function ($fila) {
                    if ($fila->empresa) {
                        return $fila->empresa->razon_social;
                    }

                    return trim(implode(' ', array_filter([
                        $fila->natural?->nombres,
                        $fila->natural?->apellido_paterno,
                        $fila->natural?->apellido_materno,
                    ]))) ?: 'Sin nombre';
                })
                ->searchable(function (Builder $query, string $search) {
                    // Permite buscar empresas por razon social.
                    $query->orWhereHas('empresa', function (Builder $consulta) use ($search) {
                        $consulta->where('razon_social', 'like', '%' . $search . '%');
                    });

                    // Permite buscar personas naturales por nombres y apellidos.
                    $query->orWhereHas('natural', function (Builder $consulta) use ($search) {
                        $consulta->where('nombres', 'like', '%' . $search . '%')
                            ->orWhere('apellido_paterno', 'like', '%' . $search . '%')
                            ->orWhere('apellido_materno', 'like', '%' . $search . '%');
                    });
                }),

            Column::make("CI / NIT")
                ->label(function ($fila) {
                    // Empresa: el NIT esta en personas y se accede desde la relacion empresa -> persona.
                    if ($fila->empresa) {
                        return $fila->empresa?->persona?->nit ?: 'S/C';
                    }

                    // Persona natural: si tiene NIT en personas se muestra; caso contrario, se usa el CI de naturals.
                    return $fila->nit ?: ($fila->natural?->ci ?: 'S/C');
                })
                ->searchable(function (Builder $query, string $search) {
                    // Busca NIT guardado en personas, usado tanto por empresa como por persona natural si corresponde.
                    $query->orWhere('nit', 'like', '%' . $search . '%');

                    // Busca CI guardado en naturals para personas naturales y responsables.
                    $query->orWhereHas('natural', function (Builder $consulta) use ($search) {
                        $consulta->where('ci', 'like', '%' . $search . '%');
                    });
                }),

            Column::make("Fecha de registro", "created_at")
                // Permite ordenar manualmente por fecha desde el encabezado de la tabla.
                ->sortable()
                ->format(function ($valor) {
                    // Muestra una fecha corta y legible para el usuario.
                    return $valor ? $valor->format('d/m/Y H:i') : 'Sin fecha';
                }),

            Column::make("Estado", "estado")
                ->label(function ($fila) {
                    return view('tablas.chip_estado', ['estado' => $fila->estado]);
                }),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('personas.accion', ['persona' => $fila]);
                }),                
        ];
    }
}

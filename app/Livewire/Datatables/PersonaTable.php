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
        $this->setTdAttributes(fn (Column $columna) => [
            'default' => true,
            'data-label' => $columna->getTitle(),
        ]);
    }

    public function builder(): Builder
    {
        return Persona::query()
            ->with([
                'natural',
                'territorio',
                'empresa.persona',
                'empresa.tipoEmpresa',
                'empresa.representanteLegalActivo.persona.natural',
                'responsabilidadesEmpresariales.rol',
            ])
            // La persona natural que representa legalmente a una empresa se muestra dentro de esa empresa.
            ->where(function (Builder $consulta) {
                $consulta
                    ->whereHas('empresa')
                    ->orWhereDoesntHave('responsabilidadesEmpresariales', function (Builder $responsable) {
                        $responsable
                            ->whereIn('estado', ['1', 'ACTIVO'])
                            ->whereHas('empresa')
                            ->whereHas('rol', fn (Builder $rol) => $rol
                                ->where('slug', 'solicitante')
                                ->where('estado', 1));
                    });
            });
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable(),

            Column::make("Empresa / persona")
                ->label(function ($fila) {
                    return view('personas.nombre-tipo-tabla', ['persona' => $fila]);
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

            Column::make("Representante legal")
                ->label(function ($fila) {
                    $responsable = $fila->empresa?->representanteLegalActivo;

                    return view('personas.representante-legal-tabla', [
                        'responsable' => $responsable,
                    ]);
                })
                ->searchable(function (Builder $query, string $search) {
                    $query->orWhereHas(
                        'empresa.representanteLegalActivo.persona.natural',
                        function (Builder $consulta) use ($search) {
                            $consulta->where('nombres', 'like', '%' . $search . '%')
                                ->orWhere('apellido_paterno', 'like', '%' . $search . '%')
                                ->orWhere('apellido_materno', 'like', '%' . $search . '%')
                                ->orWhere('ci', 'like', '%' . $search . '%');
                        }
                    );
                }),

            Column::make("CI / NIT")
                ->label(function ($fila) {
                    // Empresa: el NIT esta en personas y se accede desde la relacion empresa -> persona.
                    if ($fila->empresa) {
                        return $fila->empresa?->persona?->nit ?: '-';
                    }

                    // Persona natural: si tiene NIT en personas se muestra; caso contrario, se usa el CI de naturals.
                    return $fila->nit ?: ($fila->natural?->ci ?: '-');
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
                    return $valor ? $valor->format('d/m/Y H:i') : '-';
                }),

            Column::make("Estado", "estado")
                ->label(function ($fila) {
                    return view('tablas.chip_estado', [
                        'texto' => $fila->estado ?: 'ACTIVO',
                        'clase' => strtoupper((string) ($fila->estado ?: 'ACTIVO')) === 'ACTIVO'
                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                            : 'border-slate-200 bg-slate-100 text-slate-700',
                    ]);
                }),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('personas.accion', ['persona' => $fila]);
                }),                
        ];
    }
}

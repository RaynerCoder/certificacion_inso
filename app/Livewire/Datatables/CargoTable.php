<?php

namespace App\Livewire\Datatables;

use App\Models\Cargo;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CargoTable extends DataTableComponent
{
    protected $model = Cargo::class;

    /**
     * Configura la tabla de cargos: clave primaria y orden inicial.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Carga el area para mostrar el nombre sin consultas repetidas.
     */
    public function builder(): Builder
    {
        return Cargo::query()
            ->select('cargos.*')
            ->with('area');
    }

    /**
     * Define las columnas visibles del listado de cargos.
     */
    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable()
                ->searchable(),

            Column::make("Cargo", "nombre")
                ->format(fn ($valor) => view('tablas.texto_ajustado', ['texto' => $valor, 'clase' => 'font-semibold text-slate-800']))
                ->sortable()
                ->searchable(),

            Column::make("Area")
                ->label(fn ($fila) => view('tablas.texto_ajustado', [
                    'texto' => $fila->area?->nombre ?: 'Sin area',
                    'clase' => $fila->area ? 'text-slate-700' : 'text-rose-500 font-semibold',
                ])),

            Column::make("Descripcion", "descripcion")
                ->format(fn ($valor) => view('tablas.texto_ajustado', ['texto' => $valor ?: 'Sin descripcion'])),

            Column::make("Estado", "estado")
                ->format(fn ($valor) => view('tablas.chip_estado', [
                    'texto' => $this->estadoLiteral($valor),
                    'clase' => (string) $valor === '1'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-rose-100 text-rose-700',
                ]))
                ->sortable(),

            Column::make('Acciones')
                ->label(fn ($fila) => view('cargos.accion', ['cargo' => $fila])),
        ];
    }

    /**
     * Convierte el estado numerico a texto legible.
     */
    private function estadoLiteral($estado): string
    {
        return (string) $estado === '1' ? 'Activo' : 'Inactivo';
    }
}

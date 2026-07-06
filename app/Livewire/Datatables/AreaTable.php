<?php

namespace App\Livewire\Datatables;

use App\Models\Area;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class AreaTable extends DataTableComponent
{
    protected $model = Area::class;

    /**
     * Configura la tabla de areas.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Carga el area padre y cargos para evitar consultas repetidas por fila.
     */
    public function builder(): Builder
    {
        return Area::query()
            ->select('areas.*')
            ->with(['areaPadre', 'cargos']);
    }

    /**
     * Define las columnas visibles del listado.
     */
    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable()
                ->searchable(),

            Column::make("Area", "nombre")
                ->format(fn ($valor) => view('tablas.texto_ajustado', ['texto' => $valor, 'clase' => 'font-semibold text-slate-800']))
                ->sortable()
                ->searchable(),

            Column::make("Area superior")
                ->label(fn ($fila) => view('tablas.texto_ajustado', [
                    'texto' => $fila->areaPadre?->nombre ?: 'Sin area superior',
                    'clase' => $fila->areaPadre ? 'text-slate-700' : 'text-slate-400',
                ])),

            Column::make("Descripcion", "descripcion")
                ->format(fn ($valor) => view('tablas.texto_ajustado', ['texto' => $valor ?: 'Sin descripcion'])),

            Column::make("Cargos asignados")
                ->label(fn ($fila) => view('tablas.chip_estado', [
                    'texto' => $fila->cargos->count() . ' cargo(s)',
                    'clase' => 'bg-slate-100 text-slate-700',
                ])),

            Column::make("Estado", "estado")
                ->format(fn ($valor) => view('tablas.chip_estado', [
                    'texto' => $this->estadoLiteral($valor),
                    'clase' => (string) $valor === '1'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-rose-100 text-rose-700',
                ]))
                ->sortable(),

            Column::make('Acciones')
                ->label(fn ($fila) => view('areas.accion', ['area' => $fila])),
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

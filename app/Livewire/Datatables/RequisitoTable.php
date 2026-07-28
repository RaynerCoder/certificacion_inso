<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Requisito;

class RequisitoTable extends DataTableComponent
{
    protected $model = Requisito::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');

        $this->setComponentWrapperAttributes([
            'class' => 'requisitos-datatable',
        ]);

        $this->setTableWrapperAttributes([
            'class' => 'requisitos-table-shell overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800',
            'default' => false,
        ]);

        $this->setTableAttributes([
            'class' => 'requisitos-table w-full table-fixed',
            'default' => false,
        ]);

        $this->setTheadAttributes([
            'class' => 'bg-slate-50 dark:bg-slate-900',
            'default' => false,
        ]);

        $this->setTbodyAttributes([
            'class' => 'divide-y divide-slate-200 bg-white dark:divide-slate-700 dark:bg-slate-800',
            'default' => false,
        ]);

        $this->setThAttributes(function (Column $column): array {
            $claseAncho = match ($column->getTitle()) {
                'ID' => 'w-20 text-center',
                'Estado' => 'w-32 text-center',
                'Acciones' => 'w-44 text-right',
                default => 'text-left',
            };

            return [
                'class' => "px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300 {$claseAncho}",
            ];
        });

        $this->setTrAttributes(fn ($fila, int $indice): array => [
            'class' => $indice % 2 === 0
                ? 'bg-white transition-colors hover:bg-slate-50 dark:bg-slate-800 dark:hover:bg-slate-700/70'
                : 'bg-slate-50/60 transition-colors hover:bg-slate-100 dark:bg-slate-800/70 dark:hover:bg-slate-700/70',
            'default' => false,
        ]);

        $this->setTdAttributes(function (Column $column): array {
            $titulo = $column->getTitle();
            $configuracion = match ($titulo) {
                'ID' => ['id', 'w-20 text-center font-semibold text-slate-500'],
                'Estado' => ['estado', 'w-32 text-center'],
                'Acciones' => ['acciones', 'w-44 text-right'],
                default => ['descripcion', 'requisitos-description-cell text-slate-700 dark:text-slate-200'],
            };

            return [
                'class' => "requisitos-cell px-4 py-3 align-top text-sm {$configuracion[1]}",
                'data-column' => $configuracion[0],
                'data-label' => $titulo,
                'default' => false,
            ];
        });
    }

    public function columns(): array
    {
        return [
            Column::make("ID", "id")
                ->sortable()
                ->searchable(),

            Column::make("Descripción", "descripcion")
                ->sortable()
                ->searchable(),

            Column::make("Estado", "estado")
                ->format(fn ($estado) => view('tablas.chip_estado', [
                    'estado' => $estado,
                ]))
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('requisitos.accion', ['requisito' => $fila]);
                }),
        ];
    }
}

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
            [$columna, $alineacion, $ancho] = match ($column->getTitle()) {
                'ID' => ['id', 'text-left', 'width: 4.5rem; min-width: 4.5rem; max-width: 4.5rem; text-align: left;'],
                'Estado' => ['estado', 'text-center', 'width: 7rem; min-width: 7rem; max-width: 7rem; text-align: center;'],
                'Acciones' => ['acciones', 'text-right', 'width: 10.5rem; min-width: 10.5rem; max-width: 10.5rem; text-align: right;'],
                default => ['descripcion', 'text-left', 'text-align: left;'],
            };

            return [
                'class' => "px-4 py-3 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300 {$alineacion}",
                'data-column' => $columna,
                'style' => $ancho,
                'default' => false,
                'default-colors' => false,
                'default-styling' => false,
            ];
        });

        $this->setThSortButtonAttributes(function (Column $column): array {
            $alineacion = match ($column->getTitle()) {
                'Estado' => 'justify-center',
                default => 'justify-start',
            };

            return [
                'class' => "flex w-full items-center gap-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-300 {$alineacion}",
                'default' => false,
                'default-colors' => false,
                'default-styling' => false,
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
                'ID' => [
                    'id',
                    'text-left font-semibold text-slate-500',
                    'width: 4.5rem; min-width: 4.5rem; max-width: 4.5rem; text-align: left; vertical-align: top;',
                ],
                'Estado' => [
                    'estado',
                    'text-center',
                    'width: 7rem; min-width: 7rem; max-width: 7rem; text-align: center; vertical-align: top;',
                ],
                'Acciones' => [
                    'acciones',
                    'text-right',
                    'width: 10.5rem; min-width: 10.5rem; max-width: 10.5rem; text-align: right; vertical-align: top;',
                ],
                default => [
                    'descripcion',
                    'requisitos-description-cell text-slate-700 dark:text-slate-200',
                    'text-align: left; vertical-align: top;',
                ],
            };

            return [
                'class' => "requisitos-cell px-4 py-3 align-top text-sm {$configuracion[1]}",
                'data-column' => $configuracion[0],
                'data-label' => $titulo,
                'style' => $configuracion[2],
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

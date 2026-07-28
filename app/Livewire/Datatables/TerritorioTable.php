<?php

namespace App\Livewire\Datatables;

use App\Models\Territorio;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class TerritorioTable extends DataTableComponent
{
    protected $model = Territorio::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable(),
            Column::make('Nombre', 'nombre')
                ->sortable(),
            Column::make('Código', 'codigo')
                ->sortable(),
            Column::make('Estado', 'estado')
                ->format(fn ($estado) => view('tablas.chip_estado', [
                    'estado' => $estado,
                ]))
                ->sortable(),
            Column::make('Acciones')
                ->label(fn ($fila) => view('territorios.accion', ['territorio' => $fila])->render())
                ->html(),
        ];
    }
}

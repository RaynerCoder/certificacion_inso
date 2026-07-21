<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Territorio;

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
            Column::make("ID", "id")
                ->sortable(),
            //Column::make("Id padre territorio", "id_padre_territorio")
            //    ->sortable(),
            Column::make("Nombre", "nombre")
                ->sortable(),
            Column::make("Código", "codigo")
                ->sortable(),
            Column::make("Estado", "estado")
                ->sortable(),
            Column::make('Acciones')
                ->label(fn ($fila) => view('territorios.accion', ['territorio' => $fila])->render())
                ->html(),
        ];
    }
}

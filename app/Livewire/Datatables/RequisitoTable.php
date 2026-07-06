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
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable()
                ->searchable(),

            Column::make("Descripcion", "descripcion")
                ->sortable()
                ->searchable(),

            Column::make("Estado", "estado")
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('requisitos.accion', ['requisito' => $fila]);
                }),
        ];
    }
}

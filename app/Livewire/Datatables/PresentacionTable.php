<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Presentacion;

class PresentacionTable extends DataTableComponent
{
    protected $model = Presentacion::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Id producto", "id_producto")
                ->sortable(),
            Column::make("Url etiqueta", "url_etiqueta")
                ->sortable(),
            Column::make("Cantidad", "cantidad")
                ->sortable(),
            Column::make("Unidad", "unidad")
                ->sortable(),
            Column::make("Descripcion", "descripcion")
                ->sortable(),
            Column::make("Estado", "estado")
                ->sortable(),
            Column::make("Created at", "created_at")
                ->sortable(),
            Column::make("Updated at", "updated_at")
                ->sortable(),
        ];
    }
}

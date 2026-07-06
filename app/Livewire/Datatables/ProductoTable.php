<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Producto;

class ProductoTable extends DataTableComponent
{
    protected $model = Producto::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Id importador persona", "id_importador_persona")
                ->sortable(),
            Column::make("Id territorio pais", "id_territorio_pais")
                ->sortable(),
            Column::make("Id fabricante", "id_fabricante")
                ->sortable(),
            Column::make("Id tipo producto", "id_tipo_producto")
                ->sortable(),
            Column::make("Codigo", "codigo")
                ->sortable(),
            Column::make("Nombre comercial", "nombre_comercial")
                ->sortable(),
            Column::make("Nombre cientifico", "nombre_cientifico")
                ->sortable(),
            Column::make("Clasificacion", "clasificacion")
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

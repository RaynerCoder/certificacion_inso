<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TipoProducto;

class TipoProductoTable extends DataTableComponent
{
    protected $model = TipoProducto::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),

            Column::make("Descripción", "descripcion")
                ->sortable(),

            Column::make("Código", "codigo")
                ->sortable(),

            Column::make("Estado", "estado")
                ->sortable(),
                
            Column::make('Acciones')
                ->label(function($fila){
                    return view('tipos_productos.accion', ['tipo_producto' => $fila]);
                }),
        ];
    }
}

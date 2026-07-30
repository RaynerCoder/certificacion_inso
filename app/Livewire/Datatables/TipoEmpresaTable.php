<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TipoEmpresa;

class TipoEmpresaTable extends DataTableComponent
{
    protected $model = TipoEmpresa::class;

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
            Column::make("Descripción", "descripcion")
                ->searchable()
                ->sortable(),
            Column::make("Estado", "estado")
                ->format(fn ($estado) => view('tablas.chip_estado', [
                    'estado' => $estado,
                ]))
                ->sortable(),
            Column::make('Acciones')
                ->label(function($fila){
                    return view('tipos_empresas.accion', ['tipo_empresa' => $fila]);
                }),                
        ];
    }
}

<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TipoCertificado;

class TipoCertificadoTable extends DataTableComponent
{
    protected $model = TipoCertificado::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Nombre", "nombre")
                ->sortable(),
            Column::make("Estado", "estado")
                ->sortable(),
            Column::make('Acciones')
                ->label(function($fila){
                    return view('tipos_certificados.accion', ['tipoCertificado' => $fila]);
                }),                 
        ];
    }
}

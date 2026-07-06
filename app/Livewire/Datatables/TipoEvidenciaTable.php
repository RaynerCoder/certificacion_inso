<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\TipoEvidencia;

class TipoEvidenciaTable extends DataTableComponent
{
    protected $model = TipoEvidencia::class;

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

            Column::make("Codigo", "codigo")
                ->sortable()
                ->searchable(),

            Column::make("Nombre", "nombre")
                ->sortable()
                ->searchable(),

            Column::make("Descripcion", "descripcion")
                ->sortable()
                ->searchable(),

            Column::make("Peso maximo (MB)", "tamanio_maximo_mb")
                ->sortable(),

            Column::make("Estado", "estado")
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('tipos_evidencias.accion', ['tipoEvidencia' => $fila]);
                }),
        ];
    }
}

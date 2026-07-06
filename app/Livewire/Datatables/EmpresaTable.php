<?php

namespace App\Livewire\Datatables;

use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Empresa;
use Illuminate\Database\Eloquent\Builder;

class EmpresaTable extends DataTableComponent
{
    protected $model = Empresa::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    public function builder(): Builder
    {
        return Empresa::query()
            ->with([
                'persona.natural',
                'tipoEmpresa',
            ]);
    }    

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),

            //Column::make("Id persona", "id_persona")
            //    ->sortable(),
           // Column::make("Nombre Persona Natural", "persona.natural.nombres")->sortable(),
           // Column::make("Apellido Paterno", "persona.natural.apellido_paterno")->sortable(), 

            //Column::make("Id tipo empresa", "id_tipo_empresa")
            //    ->sortable(),
            Column::make("Tipo Empresa",  "tipoEmpresa.descripcion")->sortable(),
                
            Column::make("Razon social", "razon_social")
                ->sortable(),

            Column::make("Matricula", "matricula")
                ->sortable(),

            Column::make("Latitud", "latitud")
                ->sortable(),

            Column::make("Longitud", "longitud")
                ->sortable(),

            Column::make("Estado", "estado")
                ->sortable(),

            Column::make('Acciones')
                ->label(function($fila){
                    return view('empresas.accion', ['empresa' => $fila]);
                }),                
        ];
    }
}

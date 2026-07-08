<?php

namespace App\Livewire\Datatables;

use App\Models\TipoProducto;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

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
            Column::make('Id', 'id')->sortable(),
            Column::make('Descripcion', 'descripcion')->sortable(),
            Column::make('Codigo', 'codigo')->sortable(),
            $this->columnaEstado(),
            $this->columnaAcciones(),
        ];
    }

    private function columnaEstado(): Column
    {
        return Column::make('Estado', 'estado')
            ->format(fn ($valor) => $this->chipEstado($valor))
            ->html()
            ->sortable();
    }

    private function columnaAcciones(): Column
    {
        return Column::make('Acciones')
            ->label(fn ($tipoProducto) => view('tipos_productos.accion', ['tipo_producto' => $tipoProducto]))
            ->html();
    }

    private function chipEstado(?string $estado): string
    {
        $texto = $estado ?: 'Sin estado';
        $clase = $estado === 'ACTIVO'
            ? 'bg-emerald-50 text-emerald-700 border-emerald-200'
            : 'bg-slate-100 text-slate-600 border-slate-200';

        return '<span class="inline-flex rounded-full border px-2.5 py-1 text-xs font-bold ' . $clase . '">'
            . e(ucfirst(strtolower($texto)))
            . '</span>';
    }
}

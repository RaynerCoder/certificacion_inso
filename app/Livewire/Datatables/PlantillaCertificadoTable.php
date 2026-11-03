<?php

namespace App\Livewire\Datatables;

use App\Models\PlantillaCertificado;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PlantillaCertificadoTable extends DataTableComponent
{
    protected $model = PlantillaCertificado::class;

    /**
     * Configura el listado de todas las plantillas disponibles.
     */
    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    /**
     * Carga las relaciones que se muestran en la tabla.
     */
    public function builder(): Builder
    {
        return PlantillaCertificado::query()
            ->select('plantillas_certificados.*')
            ->with(['tipoCertificado.area'])
            ->withCount(['elementos as elementos_activos_count' => function ($consulta) {
                $consulta->where('estado', 'ACTIVO');
            }]);
    }

    /**
     * Define las columnas visibles del listado.
     */
    public function columns(): array
    {
        return [
            Column::make('ID', 'id')
                ->sortable()
                ->searchable(),

            Column::make('Plantilla', 'nombre')
                ->format(fn ($valor, $fila) => view('tablas.texto_ajustado', [
                    'texto' => $valor,
                    'clase' => 'font-semibold text-slate-800',
                ]))
                ->sortable()
                ->searchable(),

            Column::make('Tipo de certificado')
                ->label(fn ($fila) => view('tablas.texto_ajustado', [
                    'texto' => $fila->tipoCertificado?->nombre ?: 'Sin tipo de certificado',
                    'clase' => $fila->tipoCertificado ? 'text-slate-700' : 'text-rose-500 font-semibold',
                ])),

            Column::make('Área responsable')
                ->label(fn ($fila) => view('tablas.texto_ajustado', [
                    'texto' => $fila->tipoCertificado?->area?->nombre ?: 'Sin área',
                    'clase' => $fila->tipoCertificado?->area ? 'text-slate-700' : 'text-rose-500 font-semibold',
                ])),

            Column::make('Campos')
                ->label(fn ($fila) => view('tablas.chip_estado', [
                    'texto' => $fila->elementos_activos_count . ' campos',
                    'clase' => 'border-slate-200 bg-slate-100 text-slate-700',
                ])),

            Column::make('Estado')
                ->label(fn ($fila) => view('tablas.chip_estado', ['estado' => $fila->estado])),

            Column::make('Acciones')
                ->label(function ($fila) {
                    return view('certificados.plantilla_certificado.accion', [
                        'plantillaCertificado' => $fila,
                    ]);
                }),
        ];
    }

}

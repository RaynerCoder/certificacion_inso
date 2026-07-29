<?php

namespace App\Livewire\Datatables;

use App\Models\TipoCertificado;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PlantillaCertificadoTable extends DataTableComponent
{
    protected $model = TipoCertificado::class;

    /**
     * Configura el listado de plantillas por tipo de certificado.
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
        return TipoCertificado::query()
            ->select('tipos_certificados.*')
            ->with(['area', 'plantillaActiva.elementos', 'ultimaPlantilla.elementos'])
            ->withCount(['tipoCertificadoRequisitos as requisitos_activos_count' => function ($consulta) {
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

            Column::make('Tipo de certificado', 'nombre')
                ->format(fn ($valor, $fila) => view('tablas.texto_ajustado', [
                    'texto' => $valor,
                    'clase' => 'font-semibold text-slate-800',
                ]))
                ->sortable()
                ->searchable(),

            Column::make('Cantidad requisitos')
                ->label(fn ($fila) => view('tablas.chip_estado', [
                    'texto' => $fila->requisitos_activos_count . ' requisitos',
                    'clase' => 'border-slate-200 bg-slate-100 text-slate-700',
                ])),

            Column::make('Area responsable')
                ->label(fn ($fila) => view('tablas.texto_ajustado', [
                    'texto' => $fila->area?->nombre ?: 'Sin área',
                    'clase' => $fila->area ? 'text-slate-700' : 'text-rose-500 font-semibold',
                ])),

            Column::make('Campos')
                ->label(function ($fila) {
                    $plantilla = $fila->plantillaActiva ?? $fila->ultimaPlantilla;

                    return view('tablas.chip_estado', [
                        'texto' => ($plantilla?->elementos?->count() ?? 0) . ' campos',
                        'clase' => 'border-slate-200 bg-slate-100 text-slate-700',
                    ]);
                }),

            Column::make('Estado')
                ->label(function ($fila) {
                    $plantilla = $fila->plantillaActiva ?? $fila->ultimaPlantilla;

                    return view('tablas.chip_estado', [
                        'estado' => $plantilla?->estado ?? 'Sin plantilla',
                    ]);
                }),

            Column::make('Acciones')
                ->label(function ($fila) {
                    return view('certificados.plantilla_certificado.accion', [
                        'tipoCertificado' => $fila,
                        'plantillaCertificado' => $fila->plantillaActiva ?? $fila->ultimaPlantilla,
                    ]);
                }),
        ];
    }

}

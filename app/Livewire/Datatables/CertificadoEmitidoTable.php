<?php

namespace App\Livewire\Datatables;

use App\Models\Area;
use App\Models\CertificadoEmitido;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use Rappasoft\LaravelLivewireTables\Views\Filters\SelectFilter;

/**
 * Bandeja documental: una fila representa un PDF emitido, no el trámite.
 * Así se conserva visible tanto una emisión vigente como una anulación o
 * reemisión posterior del mismo trámite o lote.
 */
class CertificadoEmitidoTable extends DataTableComponent
{
    protected $model = CertificadoEmitido::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setTableName('certificados_emitidos');
        $this->setDefaultSort('fecha_emision', 'desc');
        // Las columnas con label no se agregan automáticamente al SELECT de
        // Livewire Tables. Estos identificadores permiten cargar el trámite
        // relacionado y construir las rutas de cada emisión.
        $this->setAdditionalSelects([
            'certificados_emitidos.id as id',
            'certificados_emitidos.id_certificado as id_certificado',
            'certificados_emitidos.fecha_inicio as fecha_inicio',
            'certificados_emitidos.fecha_fin as fecha_fin',
            'certificados_emitidos.fecha_anulacion as fecha_anulacion',
            'certificados_emitidos.id_usuario_anulacion as id_usuario_anulacion',
            'certificados_emitidos.motivo_anulacion as motivo_anulacion',
        ]);

        $this->setComponentWrapperAttributes([
            'class' => 'certificados-emitidos-datatable',
        ]);

        $this->setTableWrapperAttributes([
            'class' => 'overflow-x-auto rounded-lg border border-slate-200 bg-white',
            'default' => false,
        ]);

        $this->setTableAttributes([
            'class' => 'w-full table-fixed',
            'default' => false,
        ]);

        $this->setTheadAttributes([
            'class' => 'bg-slate-50',
            'default' => false,
        ]);

        $this->setTbodyAttributes([
            'class' => 'divide-y divide-slate-200 bg-white',
            'default' => false,
        ]);

        $this->setThAttributes(function (Column $column): array {
            [$identificador, $ancho] = $this->configuracionColumna($column->getTitle());

            return [
                'class' => 'px-3 py-2 text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500',
                'data-column' => $identificador,
                'style' => $ancho,
                'default' => false,
                'default-colors' => false,
                'default-styling' => false,
            ];
        });

        $this->setThSortButtonAttributes(fn (Column $column): array => [
            'class' => 'flex w-full items-center gap-1 truncate text-left text-[11px] font-semibold uppercase tracking-wide text-slate-500',
            'default' => false,
            'default-colors' => false,
            'default-styling' => false,
        ]);

        $this->setTdAttributes(function (Column $column): array {
            [$identificador, $ancho] = $this->configuracionColumna($column->getTitle());
            $datoCorto = in_array($column->getTitle(), [
                'N.º certificado',
                'Fecha de emisión',
                'Estado',
            ], true);

            // Los datos extensos deben conservarse completos. Se parten en
            // líneas dentro de su columna, en lugar de ocultarlos con "...".
            $presentacion = $datoCorto
                ? 'whitespace-nowrap'
                : 'whitespace-normal break-words leading-5';

            return [
                'class' => "px-3 py-2 align-middle text-sm text-slate-800 {$presentacion}",
                'data-column' => $identificador,
                'data-label' => $column->getTitle(),
                'style' => $ancho,
                'default' => false,
            ];
        });
    }

    public function builder(): Builder
    {
        CertificadoEmitido::actualizarEstadosVencidos();

        $consulta = CertificadoEmitido::query()
            ->with([
                'certificado.tipoCertificado.area',
                'certificado.beneficiario.natural',
                'certificado.beneficiario.empresa',
                'certificadoRegistro.registro.producto',
                'usuarioAnulacion.funcionario',
            ]);

        if (! (auth()->user()?->puede('certificados.emitir') ?? false)) {
            return $consulta->whereRaw('1 = 0');
        }

        // Esta bandeja es institucional: por ahora todo usuario con permiso
        // de emisión consulta todos los documentos oficiales, sin filtrarlos
        // por el área configurada en el tipo de certificado.
        return $consulta;
    }

    public function filters(): array
    {
        $areas = Area::query()
            ->where('estado', 1)
            ->orderBy('nombre')
            ->pluck('nombre', 'id')
            ->all();

        return [
            SelectFilter::make('Área responsable')
                ->options(['' => 'Todas las áreas'] + $areas)
                ->filter(function (Builder $consulta, string $idArea) {
                    if ($idArea === '') {
                        return;
                    }

                    $consulta->whereHas('certificado.tipoCertificado', function (Builder $tipo) use ($idArea) {
                        $tipo->where('id_area', $idArea);
                    });
                }),
        ];
    }

    public function columns(): array
    {
        return [
            Column::make('N.º certificado', 'numero_certificado')
                ->searchable()
                ->sortable(),

            Column::make('Tipo de certificado')
                ->label(fn (CertificadoEmitido $fila) => $fila->certificado?->tipoCertificado?->nombre ?: 'Sin tipo')
                ->searchable(function (Builder $consulta, string $busqueda) {
                    $consulta->orWhereHas('certificado.tipoCertificado', function (Builder $tipo) use ($busqueda) {
                        $tipo->where('nombre', 'like', '%'.$busqueda.'%');
                    });
                }),

            Column::make('Beneficiario')
                ->label(fn (CertificadoEmitido $fila) => $this->nombreBeneficiario($fila))
                ->searchable(function (Builder $consulta, string $busqueda) {
                    $consulta->orWhereHas('certificado.beneficiario.empresa', function (Builder $empresa) use ($busqueda) {
                        $empresa->where('razon_social', 'like', '%'.$busqueda.'%');
                    })->orWhereHas('certificado.beneficiario.natural', function (Builder $natural) use ($busqueda) {
                        $natural->where('nombres', 'like', '%'.$busqueda.'%')
                            ->orWhere('apellido_paterno', 'like', '%'.$busqueda.'%')
                            ->orWhere('apellido_materno', 'like', '%'.$busqueda.'%');
                    });
                }),

            Column::make('Área responsable')
                ->label(fn (CertificadoEmitido $fila) => $fila->certificado?->tipoCertificado?->area?->nombre ?: 'Sin área'),

            Column::make('Fecha de emisión', 'fecha_emision')
                ->format(fn ($fecha) => $fecha ? Carbon::parse($fecha)->format('d/m/Y H:i') : 'Sin fecha')
                ->sortable(),

            Column::make('Vigencia')
                ->label(function (CertificadoEmitido $fila) {
                    if ($fila->fecha_inicio && ! $fila->fecha_fin) {
                        return 'Indefinida';
                    }

                    return $fila->fecha_inicio && $fila->fecha_fin
                        ? $fila->fecha_inicio->format('d/m/Y').' al '.$fila->fecha_fin->format('d/m/Y')
                        : 'Sin vigencia';
                }),

            Column::make('Estado', 'estado')
                ->format(function (string $estado) {
                    $clase = match ($estado) {
                        'EMITIDO' => 'border-emerald-200 bg-emerald-50 text-emerald-700',
                        'VENCIDO' => 'border-amber-200 bg-amber-50 text-amber-700',
                        'ANULADO' => 'border-rose-200 bg-rose-50 text-rose-700',
                        default => 'border-slate-200 bg-slate-50 text-slate-700',
                    };

                    return '<span class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-bold leading-none '.$clase.'">'.e(ucfirst(mb_strtolower($estado))).'</span>';
                })
                ->html()
                ->sortable(),

            Column::make('Anulación')
                ->label(function (CertificadoEmitido $fila) {
                    if ($fila->estado !== 'ANULADO') {
                        return '—';
                    }

                    $funcionario = $fila->usuarioAnulacion?->funcionario;
                    $nombre = trim(implode(' ', array_filter([
                        $funcionario?->nombres,
                        $funcionario?->apellido_paterno,
                        $funcionario?->apellido_materno,
                    ]))) ?: ($fila->usuarioAnulacion?->name ?: 'Sin funcionario');
                    $fecha = $fila->fecha_anulacion?->format('d/m/Y H:i') ?: 'Sin fecha';
                    $motivo = e($fila->motivo_anulacion ?: 'Sin motivo registrado');

                    return '<div class="min-w-[190px] text-xs leading-5">'
                        .'<strong class="block text-rose-700">'.$fecha.'</strong>'
                        .'<span class="block font-semibold text-slate-700">'.e($nombre).'</span>'
                        .'<span class="block text-slate-500">'.$motivo.'</span>'
                        .'</div>';
                })
                ->html(),

            Column::make('Acciones')
                ->label(fn (CertificadoEmitido $fila) => view('certificados.emitir_certificado.accion', ['emision' => $fila]))
                ->html(),
        ];
    }

    private function nombreBeneficiario(CertificadoEmitido $emision): string
    {
        $persona = $emision->certificado?->beneficiario;

        if ($persona?->empresa) {
            return $persona->empresa->razon_social ?: 'Sin beneficiario';
        }

        $nombre = trim(implode(' ', array_filter([
            $persona?->natural?->nombres,
            $persona?->natural?->apellido_paterno,
            $persona?->natural?->apellido_materno,
        ])));

        return $nombre ?: 'Sin beneficiario';
    }

    /** @return array{string, string} */
    private function configuracionColumna(string $titulo): array
    {
        return match ($titulo) {
            'N.º certificado' => ['numero', 'width: 5rem; min-width: 5rem;'],
            'Tipo de certificado' => ['tipo', 'width: 13rem; min-width: 13rem;'],
            'Beneficiario' => ['beneficiario', 'width: 15rem; min-width: 15rem;'],
            'Área responsable' => ['area', 'width: 12rem; min-width: 12rem;'],
            'Fecha de emisión' => ['fecha', 'width: 10rem; min-width: 10rem;'],
            'Vigencia' => ['vigencia', 'width: 11rem; min-width: 11rem;'],
            'Estado' => ['estado', 'width: 7rem; min-width: 7rem; text-align: center;'],
            'Anulación' => ['anulacion', 'width: 12rem; min-width: 12rem;'],
            'Acciones' => ['acciones', 'width: 15.5rem; min-width: 15.5rem;'],
            default => ['general', ''],
        };
    }
}

<?php

namespace App\Livewire\Datatables;

use App\Models\Responsable;
use App\Models\Seguimiento;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class TramitadorTable extends DataTableComponent
{
    protected $model = Responsable::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        // Muestra primero los tramitadores registrados recientemente.
        $this->setDefaultSort('id', 'desc');
    }

    public function builder(): Builder
    {
        $empresa = auth()->user()
            ?->loadMissing('persona.empresa')
            ?->persona
            ?->empresa;

        return Responsable::query()
            ->select('responsables.*')
            // Datos calculados para ordenar y buscar sin cargar texto repetido en responsables.
            ->selectRaw("empresas.razon_social as nombre_empresa")
            ->selectRaw("personas_empresa.nit as nit_empresa")
            ->selectRaw("personas.correo as correo_tramitador")
            ->selectRaw("naturals.ci as ci_tramitador")
            ->selectRaw("TRIM(CONCAT_WS(' ', naturals.nombres, naturals.apellido_paterno, naturals.apellido_materno)) as nombre_tramitador")
            ->selectSub(
                Seguimiento::query()
                    ->selectRaw('COUNT(*)')
                    ->join('certificados', 'seguimientos.id_certificado', '=', 'certificados.id')
                    ->whereColumn('seguimientos.id_usuario_siguiente', 'personas.id_usuario')
                    ->where('seguimientos.estado', 'ACTIVO')
                    ->whereNull('seguimientos.fecha_derivacion')
                    ->where('certificados.estado', 'OBSERVADO'),
                'tramites_pendientes'
            )
            ->join('roles', 'responsables.id_rol', '=', 'roles.id')
            ->leftJoin('empresas', 'responsables.id_empresa', '=', 'empresas.id')
            ->leftJoin('personas as personas_empresa', 'empresas.id_persona', '=', 'personas_empresa.id')
            ->leftJoin('personas', 'responsables.id_persona', '=', 'personas.id')
            ->leftJoin('naturals', 'personas.id', '=', 'naturals.id_persona')
            // Solo se listan responsables cuyo rol corresponde a tramitador.
            ->where(function (Builder $query) {
                $query->where('roles.slug', 'tramitador')
                    ->orWhere('roles.name', 'like', '%TRAMITADOR%');
            })
            ->when(
                $empresa,
                fn (Builder $query) => $query->where('responsables.id_empresa', $empresa->id),
                fn (Builder $query) => $query->whereRaw('1 = 0')
            )
            ->with([
                'empresa.persona',
                'persona.natural',
                'persona.telefonos',
                'rol',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')
                ->sortable(),

            Column::make('Empresa', 'id_empresa')
                ->format(fn ($valorIdEmpresa, Responsable $fila) => $fila->nombre_empresa ?: 'Sin empresa')
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('empresas.razon_social', $direction))
                ->searchable(function (Builder $query, string $searchTerm) {
                    $query->orWhere('empresas.razon_social', 'like', "%{$searchTerm}%")
                        ->orWhere('personas_empresa.nit', 'like', "%{$searchTerm}%");
                }),

            Column::make('NIT', 'id_empresa')
                ->format(fn ($valorIdEmpresa, Responsable $fila) => $fila->nit_empresa ?: 'Sin NIT')
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('personas_empresa.nit', $direction)),

            Column::make('Tramitador', 'id_persona')
                ->format(fn ($valorIdPersona, Responsable $fila) => $fila->nombre_tramitador ?: 'Sin nombre')
                ->sortable(function (Builder $query, string $direction) {
                    return $query
                        ->orderBy('naturals.nombres', $direction)
                        ->orderBy('naturals.apellido_paterno', $direction)
                        ->orderBy('naturals.apellido_materno', $direction);
                })
                ->searchable(function (Builder $query, string $searchTerm) {
                    $query->orWhere('naturals.nombres', 'like', "%{$searchTerm}%")
                        ->orWhere('naturals.apellido_paterno', 'like', "%{$searchTerm}%")
                        ->orWhere('naturals.apellido_materno', 'like', "%{$searchTerm}%")
                        ->orWhere('naturals.ci', 'like', "%{$searchTerm}%");
                }),

            Column::make('CI', 'id_persona')
                ->format(fn ($valorIdPersona, Responsable $fila) => $fila->ci_tramitador ?: 'Sin CI')
                ->sortable(fn (Builder $query, string $direction) => $query->orderBy('naturals.ci', $direction)),

            Column::make('Correo', 'id_persona')
                ->format(fn ($valorIdPersona, Responsable $fila) => $fila->correo_tramitador ?: 'Sin correo')
                ->searchable(function (Builder $query, string $searchTerm) {
                    $query->orWhere('personas.correo', 'like', "%{$searchTerm}%");
                }),

            Column::make('Estado', 'estado')
                ->label(function (Responsable $fila) {
                    return view('tablas.chip_estado', [
                        'estado' => $fila->estado ?: 'ACTIVO',
                    ]);
                })
                ->html()
                ->sortable(),

            Column::make('Acciones')
                ->label(function ($fila) {
                    return view('tramitadores.accion', ['tramitador' => $fila]);
                })
                ->html(),
        ];
    }
}

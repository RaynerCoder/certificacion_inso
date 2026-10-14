<?php

namespace App\Livewire\Datatables;

use App\Models\Persona;
use App\Models\Producto;
use App\Models\Responsable;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class ProductoTable extends DataTableComponent
{
    protected $model = Producto::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    public function builder(): Builder
    {
        return Producto::query()
            ->with([
                'importadorPersona.empresa.responsables.persona.natural',
                'importadorPersona.empresa.responsables.rol',
                'importadorPersona.natural',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            Column::make('Codigo', 'codigo')->sortable(),
            $this->columnaNombreComercial(),

            // El importador conserva la relación con personas; aquí se diferencia empresa de persona natural.
            $this->columnaImportador(),

            $this->columnaEstado(),
            $this->columnaAcciones(),
        ];
    }

    private function columnaNombreComercial(): Column
    {
        return Column::make('Nombre comercial', 'nombre_comercial')
            ->format(function ($valor) {
                $nombre = filled($valor) ? (string) $valor : 'Sin nombre comercial';

                // El ancho permite que los nombres extensos bajen de línea sin ensanchar toda la tabla.
                return '<div style="min-width: 240px; max-width: 420px; white-space: normal; '
                    .'overflow-wrap: anywhere; line-height: 1.4;">'.nl2br(e($nombre)).'</div>';
            })
            ->html()
            ->sortable();
    }

    private function columnaImportador(): Column
    {
        return Column::make('Importador', 'id_importador_persona')
            // Se usa format para que Livewire incluya id_importador_persona en la consulta
            // y pueda cargar correctamente la empresa o la persona natural relacionada.
            ->format(fn ($valor, $producto) => $this->importadorParaTabla($producto->importadorPersona))
            ->html()
            ->searchable(function (Builder $query, string $search) {
                $this->buscarPersona($query, 'importadorPersona', $search);
            })
            ->sortable();
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
        return Column::make('Accion')
            ->label(fn ($producto) => view('productos.accion', ['producto' => $producto]))
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

    private function nombrePersona(?Persona $persona, string $textoVacio): string
    {
        if (!$persona) {
            return $textoVacio;
        }

        if ($persona->empresa) {
            return $persona->empresa->razon_social ?: $textoVacio;
        }

        if ($persona->natural) {
            $nombreCompleto = trim(implode(' ', array_filter([
                $persona->natural->nombres,
                $persona->natural->apellido_paterno,
                $persona->natural->apellido_materno,
            ])));

            return $nombreCompleto ?: $textoVacio;
        }

        return 'Persona #' . $persona->id;
    }

    private function importadorParaTabla(?Persona $persona): string
    {
        if (! $persona) {
            return '<span class="text-slate-500">Sin importador</span>';
        }

        if ($persona->empresa) {
            $nombreEmpresa = trim((string) $persona->empresa->razon_social) ?: 'Empresa sin razón social';
            $representantes = $persona->empresa->responsables
                ->filter(fn (Responsable $responsable) => $this->esRepresentanteLegalActivo($responsable))
                ->map(fn (Responsable $responsable) => $this->nombrePersona($responsable->persona, 'Sin nombre'))
                ->filter()
                ->unique()
                ->values();
            $etiqueta = $representantes->count() > 1 ? 'Representantes legales' : 'Representante legal';
            $detalle = $representantes->isNotEmpty()
                ? $etiqueta.': '.$representantes->implode(', ')
                : 'Sin representante legal activo';

            return '<div style="min-width: 260px; max-width: 380px; white-space: normal; line-height: 1.35;">'
                .'<div style="font-weight: 700; color: #0f172a; overflow-wrap: anywhere;">'.e($nombreEmpresa).'</div>'
                .'<div style="margin-top: 3px; color: #64748b; font-size: 0.75rem; overflow-wrap: anywhere;">'.e($detalle).'</div>'
                .'</div>';
        }

        return '<div style="min-width: 220px; max-width: 340px; white-space: normal; '
            .'font-weight: 700; line-height: 1.35; overflow-wrap: anywhere;">'
            .e($this->nombrePersona($persona, 'Persona natural sin nombre')).'</div>';
    }

    private function esRepresentanteLegalActivo(Responsable $responsable): bool
    {
        return $this->estadoActivo($responsable->estado)
            && $responsable->rol?->slug === 'solicitante'
            && $this->estadoActivo($responsable->rol?->estado)
            && $responsable->persona
            && $this->estadoActivo($responsable->persona->estado)
            && (bool) $responsable->persona->natural;
    }

    private function estadoActivo($estado): bool
    {
        return in_array(strtoupper(trim((string) $estado)), ['1', 'ACTIVO'], true);
    }

    private function buscarPersona(Builder $query, string $relacion, string $search): void
    {
        $query->orWhereHas($relacion . '.empresa', function (Builder $consulta) use ($search) {
            $consulta->where('razon_social', 'like', '%' . $search . '%');
        })
            ->orWhereHas($relacion . '.natural', function (Builder $consulta) use ($search) {
                $consulta->where('nombres', 'like', '%' . $search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $search . '%')
                    ->orWhere('ci', 'like', '%' . $search . '%');
            })
            ->orWhereHas($relacion . '.empresa.responsables.persona.natural', function (Builder $consulta) use ($search) {
                $consulta->where('nombres', 'like', '%' . $search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $search . '%')
                    ->orWhere('ci', 'like', '%' . $search . '%');
            });
    }
}

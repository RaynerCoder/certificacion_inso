<?php

namespace App\Livewire\Datatables;

use App\Models\Persona;
use App\Models\Producto;
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
                'importadorPersona.empresa',
                'importadorPersona.natural',
                'fabricante',
                'tipoProducto',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            Column::make('Codigo', 'codigo')->sortable(),
            Column::make('Nombre comercial', 'nombre_comercial')->sortable(),

            // En la base se guardan IDs; aqui se muestran nombres para que la tabla sea clara.
            $this->columnaImportador(),
            $this->columnaFabricante(),
            $this->columnaTipoProducto(),

            $this->columnaEstado(),
            $this->columnaAcciones(),
        ];
    }

    private function columnaImportador(): Column
    {
        return Column::make('Importador', 'id_importador_persona')
            ->label(fn ($producto) => $this->nombrePersona($producto->importadorPersona, 'Sin importador'))
            ->searchable(function (Builder $query, string $search) {
                $this->buscarPersona($query, 'importadorPersona', $search);
            })
            ->sortable();
    }

    private function columnaFabricante(): Column
    {
        return Column::make('Fabricante', 'id_fabricante')
            ->format(fn ($valor, $producto) => $producto->fabricante?->nombre ?: 'Sin fabricante')
            ->searchable(function (Builder $query, string $search) {
                $query->orWhereHas('fabricante', function (Builder $consulta) use ($search) {
                    $consulta->where('nombre', 'like', '%' . $search . '%')
                        ->orWhere('razon_social', 'like', '%' . $search . '%');
                });
            })
            ->sortable();
    }

    private function columnaTipoProducto(): Column
    {
        return Column::make('Tipo producto', 'id_tipo_producto')
            ->format(fn ($valor, $producto) => $producto->tipoProducto?->descripcion ?: 'Sin tipo')
            ->searchable(function (Builder $query, string $search) {
                $query->orWhereHas('tipoProducto', function (Builder $consulta) use ($search) {
                    $consulta->where('descripcion', 'like', '%' . $search . '%')
                        ->orWhere('codigo', 'like', '%' . $search . '%');
                });
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
            });
    }
}

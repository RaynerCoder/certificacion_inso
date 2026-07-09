<?php

namespace App\Livewire\Datatables;

use App\Models\Presentacion;
use App\Models\Registro;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class RegistroTable extends DataTableComponent
{
    protected $model = Registro::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    public function builder(): Builder
    {
        return Registro::query()
            ->with([
                'producto.fabricante',
                'producto.tipoProducto',
                'presentacion.catalogoUnidad',
                'catalogoUnidad',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            $this->columnaProducto(),
            Column::make('Codigo autorizacion', 'codigo_autorizacion')->sortable(),
            $this->columnaFechaVigencia(),
            Column::make('Cantidad', 'cantidad')->sortable(),
            Column::make('Unidad', 'id_catalogo_unidad')
                ->format(fn ($valor, $registro) => $this->textoCatalogoUnidad($registro))
                ->sortable(),
            $this->columnaPresentacion(),
            $this->columnaEstado(),
        ];
    }

    private function columnaProducto(): Column
    {
        return Column::make('Producto', 'id_producto')
            ->format(fn ($valor, $registro) => $this->textoProducto($registro))
            ->html()
            ->searchable(function (Builder $query, string $search) {
                $query->orWhereHas('producto', function (Builder $consulta) use ($search) {
                    $consulta->where('codigo', 'like', '%' . $search . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $search . '%')
                        ->orWhere('nombre_cientifico', 'like', '%' . $search . '%');
                });
            })
            ->sortable();
    }

    private function columnaFechaVigencia(): Column
    {
        return Column::make('Fecha vigencia', 'fecha_vigencia')
            ->format(fn ($valor) => $valor ? Carbon::parse($valor)->format('d/m/Y') : 'Sin fecha')
            ->sortable();
    }

    private function columnaPresentacion(): Column
    {
        return Column::make('Presentacion', 'id_presentacion')
            ->format(fn ($valor, $registro) => $this->textoPresentacion($registro))
            ->html()
            ->sortable();
    }

    private function columnaEstado(): Column
    {
        return Column::make('Estado', 'estado')
            ->format(fn ($valor) => $this->chipEstado($valor))
            ->html()
            ->sortable();
    }

    private function textoProducto(Registro $registro): string
    {
        $producto = $registro->producto;

        if (!$producto) {
            return '<span class="text-slate-500">Sin producto</span>';
        }

        $nombre = $producto->nombre_comercial ?: 'Sin nombre comercial';
        $codigo = $producto->codigo ?: 'Sin codigo';
        $tipo = $producto->tipoProducto?->descripcion ?: 'Sin tipo';
        $fabricante = $producto->fabricante?->nombre ?: 'Sin fabricante';

        return '<div class="max-w-[260px] whitespace-normal break-words leading-snug">'
            . '<div class="font-bold text-slate-800">' . e($nombre) . '</div>'
            . '<div class="text-xs font-semibold text-slate-500">' . e($codigo) . ' | ' . e($tipo) . '</div>'
            . '<div class="text-xs text-slate-500">' . e($fabricante) . '</div>'
            . '</div>';
    }

    private function textoPresentacion(Registro $registro): string
    {
        $presentacion = $registro->presentacion;

        if (!$presentacion) {
            return '<span class="text-slate-500">Sin presentacion</span>';
        }

        $cantidad = trim(($presentacion->cantidad ?? '') . ' ' . $this->textoCatalogoUnidad($presentacion)) ?: 'Sin cantidad';
        $descripcion = $presentacion->descripcion ?: 'Sin descripcion';

        return '<div class="max-w-[320px] whitespace-normal break-words leading-snug">'
            . '<div class="font-bold text-slate-800">' . e($cantidad) . '</div>'
            . '<div class="text-xs text-slate-500">' . e($descripcion) . '</div>'
            . '</div>';
    }

    private function textoCatalogoUnidad(Registro|Presentacion $modelo): string
    {
        $unidad = $modelo->catalogoUnidad;

        if (!$unidad) {
            return 'Sin unidad';
        }

        return trim($unidad->nombre . ($unidad->abreviatura ? ' (' . $unidad->abreviatura . ')' : ''));
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


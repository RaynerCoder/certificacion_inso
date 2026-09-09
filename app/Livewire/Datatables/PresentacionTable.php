<?php

namespace App\Livewire\Datatables;

use App\Models\Presentacion;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class PresentacionTable extends DataTableComponent
{
    protected $model = Presentacion::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    public function builder(): Builder
    {
        return Presentacion::query()
            ->with([
                'producto.fabricante',
                'producto.clasificacionToxicologica',
                'catalogoUnidad',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Nro.', 'id')->sortable(),
            $this->columnaProducto(),
            $this->columnaEtiqueta(),
            Column::make('Cantidad de presentación', 'cantidad')->sortable(),
            Column::make('Unidad de presentación', 'id_catalogo_unidad')
                ->format(fn ($valor, $presentacion) => $this->textoCatalogoUnidad($presentacion))
                ->sortable(),
            $this->columnaDescripcion(),
            $this->columnaEstado(),
        ];
    }

    private function columnaProducto(): Column
    {
        return Column::make('Producto', 'id_producto')
            ->format(fn ($valor, $presentacion) => $this->textoProducto($presentacion))
            ->html()
            ->searchable(function (Builder $query, string $search) {
                $query->orWhereHas('producto', function (Builder $consulta) use ($search) {
                    $consulta->where('codigo', 'like', '%' . $search . '%')
                        ->orWhere('nombre_comercial', 'like', '%' . $search . '%')
                        ->orWhere('nombre_tecnico', 'like', '%' . $search . '%');
                });
            })
            ->sortable();
    }

    private function columnaEtiqueta(): Column
    {
        return Column::make('Etiqueta', 'url_etiqueta')
            ->format(fn ($valor, Presentacion $presentacion) => $this->enlaceArchivo($presentacion))
            ->html();
    }

    private function columnaDescripcion(): Column
    {
        return Column::make('Descripción de presentación', 'descripcion')
            ->format(fn ($valor) => $this->textoConSalto($valor ?: 'No registrada'))
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

    private function textoProducto(Presentacion $presentacion): string
    {
        $producto = $presentacion->producto;

        if (!$producto) {
            return '<span class="text-slate-500">Sin producto</span>';
        }

        $nombre = $producto->nombre_comercial ?: 'Sin nombre comercial';
        $codigo = $producto->codigo ?: 'Sin codigo';
        $clasificacionToxicologica = trim(
            ($producto->clasificacionToxicologica?->codigo ?? '') . ' - ' .
            ($producto->clasificacionToxicologica?->descripcion ?? ''),
            ' -'
        ) ?: 'No registrada';
        $fabricante = $producto->fabricante?->razon_social
            ?: ($producto->fabricante?->nombre ?: 'No registrado');

        return '<div class="max-w-[260px] whitespace-normal break-words leading-snug">'
            . '<div class="font-bold text-slate-800">' . e($nombre) . '</div>'
            . '<div class="mt-1 text-xs text-slate-500"><strong>Código:</strong> ' . e($codigo) . '</div>'
            . '<div class="text-xs text-slate-500"><strong>Clasificación toxicológica:</strong> ' . e($clasificacionToxicologica) . '</div>'
            . '<div class="text-xs text-slate-500"><strong>Fabricante:</strong> ' . e($fabricante) . '</div>'
            . '</div>';
    }

    private function enlaceArchivo(Presentacion $presentacion): string
    {
        if (!$presentacion->url_etiqueta) {
            return '<span class="inline-flex rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-bold text-slate-500">Sin archivo</span>';
        }

        return '<a href="' . e(route('productos_presentaciones_etiqueta', $presentacion)) . '" target="_blank" '
            . 'class="inline-flex rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-700">'
            . 'Ver etiqueta'
            . '</a>';
    }

    private function textoConSalto(string $texto): string
    {
        return '<div class="max-w-[320px] whitespace-normal break-words leading-snug text-slate-700">'
            . e($texto)
            . '</div>';
    }

    private function textoCatalogoUnidad(Presentacion $presentacion): string
    {
        $unidad = $presentacion->catalogoUnidad;

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


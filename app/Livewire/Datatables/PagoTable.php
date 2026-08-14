<?php

namespace App\Livewire\Datatables;

use App\Models\Persona;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;
use App\Models\Pago;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class PagoTable extends DataTableComponent
{
    protected $model = Pago::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setDefaultSort('id', 'desc');
    }

    // Carga las relaciones que se muestran en columnas para evitar consultas por cada fila.
    public function builder(): Builder
    {
        return Pago::query()
            ->with([
                'clientePersona.natural',
                'clientePersona.empresa',
                'certificados',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('ID pago', 'id')
                ->sortable(),

            Column::make('Trámite')
                ->label(fn (Pago $fila) => view('pagos.tramite', [
                    'certificado' => $fila->certificados->first(),
                ])),

            Column::make('Beneficiario', 'id_cliente')
                ->format(fn ($valor, Pago $fila) => $this->nombrePersona($fila->clientePersona))
                ->sortable(),

            Column::make('Fecha pago', 'fecha')
                ->format(fn ($valor) => $this->fechaCorta($valor))
                ->sortable(),

            Column::make('Monto', 'monto')
                ->format(fn ($valor) => number_format((float) $valor, 2, ',', '.') . ' Bs.')
                ->sortable(),

            Column::make('Número de factura', 'factura')
                ->format(fn ($valor) => filled($valor) ? $valor : 'Sin factura')
                ->sortable()
                ->searchable(),

            Column::make('Acciones')
                ->label(fn (Pago $fila) => view('pagos.accion', [
                    'pago' => $fila,
                    'certificado' => $fila->certificados->first(),
                ])),
        ];
    }

    // Devuelve una fecha corta para tablas; si no hay dato, no inventa estados.
    private function fechaCorta($fecha): string
    {
        return $fecha ? Carbon::parse($fecha)->format('d/m/Y') : 'Sin fecha';
    }

    // Nombre del cliente asociado al pago: empresa si existe, persona natural si corresponde.
    private function nombrePersona(?Persona $persona): string
    {
        if (!$persona) {
            return 'Sin cliente';
        }

        if ($persona->empresa) {
            return $persona->empresa->razon_social ?: 'Sin razon social';
        }

        if ($persona->natural) {
            return trim(implode(' ', array_filter([
                $persona->natural->nombres,
                $persona->natural->apellido_paterno,
                $persona->natural->apellido_materno,
            ]))) ?: 'Sin nombre';
        }

        return 'Persona #' . $persona->id;
    }

}

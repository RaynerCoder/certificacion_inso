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
    }

    // Carga las relaciones que se muestran en columnas para evitar consultas por cada fila.
    public function builder(): Builder
    {
        return Pago::query()
            ->with([
                'procedencia',
                'clientePersona.natural',
                'clientePersona.empresa',
                'funcionarioUsuario.funcionario',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make('Id', 'id')
                ->sortable(),

            Column::make('Procedencia', 'id_procedencia')
                ->format(fn ($valor, Pago $fila) => $fila->procedencia?->descripcion ?? 'Sin procedencia')
                ->sortable(),

            Column::make('Tipo de pago', 'tipo_pago')
                ->format(fn ($valor) => Pago::TIPOS_PAGOS[$valor] ?? ($valor ?: 'Sin tipo'))
                ->sortable(),

            Column::make('Fecha pago', 'fecha')
                ->format(fn ($valor) => $this->fechaCorta($valor))
                ->sortable(),

            Column::make('Monto', 'monto')
                ->format(fn ($valor) => number_format((float) $valor, 2, ',', '.') . ' Bs.')
                ->sortable(),

            Column::make('Cliente', 'id_cliente')
                ->format(fn ($valor, Pago $fila) => $this->nombrePersona($fila->clientePersona))
                ->sortable(),

            Column::make('Registrado por', 'id_funcionario')
                ->format(fn ($valor, Pago $fila) => $this->nombreFuncionario($fila->funcionarioUsuario))
                ->sortable(),

            Column::make('Fecha registro', 'fecha_validacion')
                ->format(fn ($valor) => $this->fechaCorta($valor))
                ->sortable(),

            Column::make('Comprobante', 'comprobante')
                ->format(fn ($valor) => $valor ? 'Con PDF' : 'Sin PDF')
                ->sortable(),
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

    // Nombre completo del funcionario; evita mostrar solo el usuario corto del sistema.
    private function nombreFuncionario($usuario): string
    {
        if (!$usuario) {
            return 'Sin funcionario';
        }

        $funcionario = $usuario->funcionario;

        if ($funcionario) {
            return trim(implode(' ', array_filter([
                $funcionario->nombres,
                $funcionario->apellido_paterno,
                $funcionario->apellido_materno,
            ]))) ?: 'Sin funcionario';
        }

        return $usuario->email ?: ($usuario->name ?: 'Sin funcionario');
    }
}

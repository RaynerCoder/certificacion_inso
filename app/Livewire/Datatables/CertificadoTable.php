<?php

namespace App\Livewire\Datatables;

use App\Models\Certificado;
use App\Models\Persona;
use Illuminate\Database\Eloquent\Builder;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class CertificadoTable extends DataTableComponent
{
    protected $model = Certificado::class;

    public function configure(): void
    {
        $this->setPrimaryKey('id');
    }

    // Consulta principal del listado.
    // Usa relaciones Eloquent para no mezclar beneficiario, tramitador, natural y empresa.
    public function builder(): Builder
    {
        // Livewire puede refrescar la tabla sin pasar por el controlador index.
        // Por eso tambien se corrigen aqui los certificados vencidos antes de consultar.
        Certificado::actualizarCertificadosEstadosVencidos();

        // Una persona puede tener datos de natural o de empresa; el certificado solo guarda el id_persona.
        return Certificado::query()
            // Usa relaciones Eloquent: beneficiario/tramitador pueden ser persona natural o empresa.
            ->with([
                'tipoCertificado',
                'beneficiario.natural',
                'beneficiario.empresa',
                'tramitador.natural',
                'tramitador.empresa',
                'certificadoRequisitos',
            ]);
    }

    public function columns(): array
    {
        return [
            Column::make("Id", "id")
                ->sortable(),
            Column::make("Codigo", "codigo")
                ->searchable()
                ->sortable(),
            Column::make("Tipo de certificado", "id_tipo_certificado")
                ->format(fn ($valor, $fila) => $fila->tipoCertificado?->nombre ?: 'Sin tipo')
                ->searchable(function (Builder $query, string $search) {
                    $query->orWhereHas('tipoCertificado', function (Builder $consulta) use ($search) {
                        $consulta->where('nombre', 'like', '%' . $search . '%');
                    });
                })
                ->sortable(),
            Column::make("Beneficiario", "id_persona_beneficiario")
                ->format(fn ($valor, $fila) => $this->nombrePersona($fila->beneficiario, 'Sin beneficiario'))
                ->searchable(function (Builder $query, string $search) {
                    $this->buscarPersonaRelacionada($query, 'beneficiario', $search);
                }),
            Column::make("Tramitador", "id_persona_tramitador")
                ->format(fn ($valor, $fila) => $this->nombrePersona($fila->tramitador, 'Sin tramitador'))
                ->searchable(function (Builder $query, string $search) {
                    $this->buscarPersonaRelacionada($query, 'tramitador', $search);
                }),
            Column::make("Fecha inicio", "fecha_inicio")
                ->format(fn ($valor) => $valor ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y') : 'Sin fecha')
                ->sortable(),
            Column::make("Fecha fin", "fecha_fin")
                ->format(fn ($valor) => $valor ? \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y') : 'Sin fecha')
                ->sortable(),
            Column::make("Requisitos")
                ->label(function ($fila) {
                    $total = $fila->certificadoRequisitos->count();
                    $cumplidos = $fila->certificadoRequisitos->where('cumple', 'SI')->count();

                    return $total > 0 ? $cumplidos . '/' . $total : 'Sin requisitos';
                }),

            Column::make("Estado", "estado")
                ->format(function ($valor) {
                    $clase = Certificado::claseEstadoCertificado($valor);
                    $texto = Certificado::textoEstadoCertificado($valor);

                    // Chip compacto: identifica el estado sin parecer un boton de accion.
                    return '<span class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-black leading-none ' . $clase . '">' . e($texto) . '</span>';
                })
                ->html()
                ->sortable(),

            Column::make('Acciones')
                ->label(function ($fila) {
                    return view('certificados.accion', ['certificado' => $fila]);
                }),
        ];
    }

    // Devuelve razon social si la persona es juridica; si no, devuelve nombre natural.
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

    // Busca por razon social si es empresa o por nombres/apellidos si es persona natural.
    private function buscarPersonaRelacionada(Builder $query, string $relacion, string $search): void
    {
        $query->orWhereHas($relacion . '.empresa', function (Builder $consulta) use ($search) {
            $consulta->where('razon_social', 'like', '%' . $search . '%');
        })
            ->orWhereHas($relacion . '.natural', function (Builder $consulta) use ($search) {
                $consulta->where('nombres', 'like', '%' . $search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $search . '%');
            });
    }
}

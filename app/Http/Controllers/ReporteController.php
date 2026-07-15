<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Certificado;
use App\Models\RevisionRequisito;
use App\Models\Seguimiento;
use App\Models\TipoCertificado;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ReporteController extends Controller
{
    /**
     * Muestra los indicadores principales del sistema certificador.
     */
    public function index(Request $request)
    {
        $filtros = $this->validarFiltros($request);
        $certificados = $this->certificadosFiltrados($filtros);

        return view('reportes.index', [
            'filtros' => $filtros,
            'tiposCertificados' => TipoCertificado::orderBy('nombre')->get(),
            'areas' => Area::orderBy('nombre')->get(),
            'resumen' => $this->resumenGeneral($certificados),
            'rankingSolicitantes' => $this->rankingSolicitantes($certificados),
            'estadosTramite' => $this->estadosTramite($certificados),
            'tramitesPorMes' => $this->tramitesPorMes($certificados),
            'requisitosObservados' => $this->requisitosObservados($filtros),
            'cargaFuncionarios' => $this->cargaFuncionarios($filtros),
        ]);
    }

    /**
     * Valida filtros de consulta y deja fechas por defecto para no cargar todo sin control.
     */
    private function validarFiltros(Request $request): array
    {
        $datos = $request->validate([
            'fecha_desde' => ['nullable', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'id_tipo_certificado' => ['nullable', 'integer', 'exists:tipos_certificados,id'],
            'id_area' => ['nullable', 'integer', 'exists:areas,id'],
            'estado' => ['nullable', 'string', 'max:50'],
        ], [], [
            'fecha_desde' => 'fecha desde',
            'fecha_hasta' => 'fecha hasta',
            'id_tipo_certificado' => 'tipo de certificado',
            'id_area' => 'area',
            'estado' => 'estado',
        ]);

        return [
            'fecha_desde' => $datos['fecha_desde'] ?? now()->startOfMonth()->toDateString(),
            'fecha_hasta' => $datos['fecha_hasta'] ?? now()->toDateString(),
            'id_tipo_certificado' => $datos['id_tipo_certificado'] ?? null,
            'id_area' => $datos['id_area'] ?? null,
            'estado' => $datos['estado'] ?? null,
        ];
    }

    /**
     * Carga los tramites usados por todos los indicadores de la pantalla.
     */
    private function certificadosFiltrados(array $filtros): Collection
    {
        return Certificado::query()
            ->with([
                'tipoCertificado.area',
                'beneficiario.empresa',
                'beneficiario.natural',
                'tramitador.empresa',
                'tramitador.natural',
            ])
            ->when($filtros['fecha_desde'], fn ($query) => $query->whereDate('fecha_inicio', '>=', $filtros['fecha_desde']))
            ->when($filtros['fecha_hasta'], fn ($query) => $query->whereDate('fecha_inicio', '<=', $filtros['fecha_hasta']))
            ->when($filtros['id_tipo_certificado'], fn ($query) => $query->where('id_tipo_certificado', $filtros['id_tipo_certificado']))
            ->when($filtros['estado'], fn ($query) => $query->where('estado', $filtros['estado']))
            ->when($filtros['id_area'], function ($query) use ($filtros) {
                $query->whereHas('tipoCertificado', fn ($tipo) => $tipo->where('id_area', $filtros['id_area']));
            })
            ->get();
    }

    /**
     * Indicadores superiores del reporte.
     */
    private function resumenGeneral(Collection $certificados): array
    {
        $finalizados = $certificados->filter(fn ($certificado) => $this->esFinalizado($certificado->estado));
        $observados = $certificados->filter(fn ($certificado) => $certificado->estado === 'OBSERVADO');

        return [
            'iniciados' => $certificados->count(),
            'emitidos' => $certificados->where('estado', 'EMITIDO')->count(),
            'observados' => $observados->count(),
            'atrasados' => $certificados->filter(fn ($certificado) => $this->diasDesdeInicio($certificado) > 10 && !$this->esFinalizado($certificado->estado))->count(),
            'promedio_emision' => $this->promedioDias($finalizados),
            'promedio_revision' => $this->promedioRevisionTecnica($certificados),
            'promedio_correccion' => $this->promedioCorreccionSolicitante($certificados),
        ];
    }

    /**
     * Ranking de empresas o personas naturales que mas tramites registraron.
     */
    private function rankingSolicitantes(Collection $certificados): Collection
    {
        return $certificados
            ->groupBy(fn ($certificado) => $certificado->id_persona_beneficiario)
            ->map(function (Collection $grupo) {
                $primero = $grupo->first();
                $finalizados = $grupo->filter(fn ($certificado) => $this->esFinalizado($certificado->estado));

                return [
                    'nombre' => $this->nombrePersona($primero->beneficiario),
                    'total' => $grupo->count(),
                    'observados' => $grupo->where('estado', 'OBSERVADO')->count(),
                    'finalizados' => $finalizados->count(),
                    'promedio_emision' => $this->promedioDias($finalizados),
                ];
            })
            ->sortByDesc('total')
            ->take(8)
            ->values();
    }

    /**
     * Agrupa estados para la dona del dashboard.
     */
    private function estadosTramite(Collection $certificados): Collection
    {
        return $certificados
            ->groupBy(fn ($certificado) => $certificado->estado ?: 'SIN_ESTADO')
            ->map(fn (Collection $grupo, string $estado) => [
                'estado' => $estado,
                'texto' => $this->textoEstado($estado),
                'total' => $grupo->count(),
                'porcentaje' => $certificados->count() > 0 ? round(($grupo->count() * 100) / $certificados->count(), 1) : 0,
            ])
            ->sortByDesc('total')
            ->values();
    }

    /**
     * Conteo mensual de tramites iniciados.
     */
    private function tramitesPorMes(Collection $certificados): Collection
    {
        return $certificados
            ->filter(fn ($certificado) => $certificado->fecha_inicio)
            ->groupBy(fn ($certificado) => Carbon::parse($certificado->fecha_inicio)->format('Y-m'))
            ->map(fn (Collection $grupo, string $mes) => [
                'mes' => Carbon::createFromFormat('Y-m', $mes)->translatedFormat('M Y'),
                'total' => $grupo->count(),
            ])
            ->sortKeys()
            ->values();
    }

    /**
     * Requisitos con mas observaciones dentro del periodo filtrado.
     */
    private function requisitosObservados(array $filtros): Collection
    {
        return RevisionRequisito::query()
            ->with('requisitoCertificado.requisito')
            ->whereHas('observacionesRequisitos')
            ->whereHas('requisitoCertificado.certificado', function ($query) use ($filtros) {
                $query
                    ->when($filtros['fecha_desde'], fn ($consulta) => $consulta->whereDate('fecha_inicio', '>=', $filtros['fecha_desde']))
                    ->when($filtros['fecha_hasta'], fn ($consulta) => $consulta->whereDate('fecha_inicio', '<=', $filtros['fecha_hasta']))
                    ->when($filtros['id_tipo_certificado'], fn ($consulta) => $consulta->where('id_tipo_certificado', $filtros['id_tipo_certificado']))
                    ->when($filtros['estado'], fn ($consulta) => $consulta->where('estado', $filtros['estado']))
                    ->when($filtros['id_area'], function ($consulta) use ($filtros) {
                        $consulta->whereHas('tipoCertificado', fn ($tipo) => $tipo->where('id_area', $filtros['id_area']));
                    });
            })
            ->get()
            ->groupBy(fn ($revision) => $revision->requisitoCertificado?->requisito?->descripcion ?? 'Sin requisito')
            ->map(fn (Collection $grupo, string $requisito) => [
                'requisito' => $requisito,
                'total' => $grupo->count(),
            ])
            ->sortByDesc('total')
            ->take(6)
            ->values();
    }

    /**
     * Carga actual por funcionario segun los movimientos activos de seguimiento.
     */
    private function cargaFuncionarios(array $filtros): Collection
    {
        return Seguimiento::query()
            ->with('usuarioSiguiente.funcionario.cargos.area')
            ->whereNotNull('id_usuario_siguiente')
            ->whereIn('estado', ['PENDIENTE', 'EN_REVISION', 'DERIVADO', 'CORRECCION_RECIBIDA'])
            ->whereHas('certificado', function ($query) use ($filtros) {
                $query
                    ->when($filtros['fecha_desde'], fn ($consulta) => $consulta->whereDate('fecha_inicio', '>=', $filtros['fecha_desde']))
                    ->when($filtros['fecha_hasta'], fn ($consulta) => $consulta->whereDate('fecha_inicio', '<=', $filtros['fecha_hasta']))
                    ->when($filtros['id_tipo_certificado'], fn ($consulta) => $consulta->where('id_tipo_certificado', $filtros['id_tipo_certificado']))
                    ->when($filtros['estado'], fn ($consulta) => $consulta->where('estado', $filtros['estado']));
            })
            ->get()
            ->groupBy('id_usuario_siguiente')
            ->map(function (Collection $grupo) {
                $usuario = $grupo->first()->usuarioSiguiente;
                $cargo = $usuario?->funcionario?->cargos?->first();

                return [
                    'funcionario' => $this->nombreUsuario($usuario),
                    'area' => $cargo?->area?->nombre ?? 'Sin area',
                    'activos' => $grupo->count(),
                    'atrasados' => $grupo->filter(fn ($seguimiento) => $this->diasDesdeFecha($seguimiento->fecha_inicio) > 10)->count(),
                    'promedio_revision' => $this->promedioDiasSeguimientos($grupo),
                ];
            })
            ->sortByDesc('activos')
            ->take(8)
            ->values();
    }

    private function promedioRevisionTecnica(Collection $certificados): int
    {
        $seguimientos = Seguimiento::query()
            ->whereIn('id_certificado', $certificados->pluck('id'))
            ->whereNotNull('fecha_inicio')
            ->get();

        return $this->promedioDiasSeguimientos($seguimientos);
    }

    private function promedioCorreccionSolicitante(Collection $certificados): int
    {
        $seguimientos = Seguimiento::query()
            ->whereIn('id_certificado', $certificados->pluck('id'))
            ->whereIn('estado', ['CORRECCION_SOLICITADA', 'CORRECCION_RECIBIDA'])
            ->whereNotNull('fecha_inicio')
            ->get();

        return $this->promedioDiasSeguimientos($seguimientos);
    }

    private function promedioDias(Collection $certificados): int
    {
        $dias = $certificados
            ->map(fn ($certificado) => $this->diasEntreFechas($certificado->fecha_inicio, $certificado->fecha_fin))
            ->filter(fn ($dias) => $dias !== null);

        return $dias->count() > 0 ? (int) round($dias->avg()) : 0;
    }

    private function promedioDiasSeguimientos(Collection $seguimientos): int
    {
        $dias = $seguimientos
            ->map(fn ($seguimiento) => $this->diasEntreFechas($seguimiento->fecha_inicio, $seguimiento->fecha_final ?: $seguimiento->fecha_derivacion ?: now()))
            ->filter(fn ($dias) => $dias !== null);

        return $dias->count() > 0 ? (int) round($dias->avg()) : 0;
    }

    private function diasDesdeInicio(Certificado $certificado): int
    {
        return $this->diasEntreFechas($certificado->fecha_inicio, now()) ?? 0;
    }

    private function diasDesdeFecha($fecha): int
    {
        return $this->diasEntreFechas($fecha, now()) ?? 0;
    }

    private function diasEntreFechas($inicio, $fin): ?int
    {
        if (!$inicio || !$fin) {
            return null;
        }

        return Carbon::parse($inicio)->startOfDay()->diffInDays(Carbon::parse($fin)->startOfDay());
    }

    private function esFinalizado(?string $estado): bool
    {
        return in_array($estado, ['FINALIZADO', 'APROBADO', 'EMITIDO'], true);
    }

    private function nombrePersona($persona): string
    {
        if (!$persona) {
            return 'Sin beneficiario';
        }

        if ($persona->empresa) {
            return $persona->empresa->razon_social ?: 'Empresa sin razon social';
        }

        if ($persona->natural) {
            return trim(implode(' ', array_filter([
                $persona->natural->nombres,
                $persona->natural->apellido_paterno,
                $persona->natural->apellido_materno,
            ]))) ?: 'Persona natural sin nombre';
        }

        return $persona->correo ?: 'Persona sin datos';
    }

    private function nombreUsuario($usuario): string
    {
        if (!$usuario?->funcionario) {
            return $usuario?->name ?: 'Sin funcionario';
        }

        return trim(implode(' ', array_filter([
            $usuario->funcionario->nombres,
            $usuario->funcionario->apellido_paterno,
            $usuario->funcionario->apellido_materno,
        ]))) ?: ($usuario->name ?: 'Sin funcionario');
    }

    private function textoEstado(?string $estado): string
    {
        return match ($estado) {
            'EN_REVISION' => 'En revision',
            'OBSERVADO' => 'Observado',
            'APROBADO' => 'Aprobado',
            'FINALIZADO' => 'Finalizado',
            'EMITIDO' => 'Emitido',
            'RECHAZADO' => 'Rechazado',
            'CORRECCION_SOLICITADA' => 'Correccion solicitada',
            'CORRECCION_RECIBIDA' => 'Correccion recibida',
            'PENDIENTE' => 'Pendiente',
            default => $estado ? str_replace('_', ' ', ucfirst(strtolower($estado))) : 'Sin estado',
        };
    }
}

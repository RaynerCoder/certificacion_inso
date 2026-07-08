<?php

namespace App\Livewire\Datatables;

use App\Models\Certificado;
use App\Models\Persona;
use App\Models\Seguimiento;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Rappasoft\LaravelLivewireTables\DataTableComponent;
use Rappasoft\LaravelLivewireTables\Views\Column;

class SeguimientoTable extends DataTableComponent
{
    protected $model = Seguimiento::class;

    // Bandeja activa:
    // - recibidas: tramites que debe atender el funcionario.
    // - enviadas: tramites propios del solicitante.
    // - todos: consulta general institucional.
    // - finalizados: tramites cerrados y listos para imprimir.
    public string $bandeja = 'recibidas';

    public function configure(): void
    {
        $this->setPrimaryKey('id');
        $this->setTableName('seguimientos_' . $this->bandeja);
    }

    /*
    |--------------------------------------------------------------------------
    | Consulta principal
    |--------------------------------------------------------------------------
    */

    public function builder(): Builder
    {
        $query = Seguimiento::query()
            ->with($this->relacionesNecesarias())
            ->whereNull('id_seguimiento_padre');

        if (!auth()->check()) {
            return $query->whereRaw('1 = 0');
        }

        return match ($this->bandeja) {
            'enviadas' => $this->filtrarSolicitudesEnviadas($query),
            'todos' => $this->filtrarTodosLosTramites($query),
            'finalizados' => $this->filtrarTramitesFinalizados($query),
            default => $this->filtrarSolicitudesRecibidas($query),
        };
    }

    // Relaciones usadas por las columnas. Mantenerlas juntas evita consultas repetidas por fila.
    private function relacionesNecesarias(): array
    {
        return [
            'certificado.tipoCertificado',
            'certificado.beneficiario.natural',
            'certificado.beneficiario.empresa',
            'certificado.tramitador.natural',
            'certificado.tramitador.empresa',
            'certificado.certificadoRequisitos',
            'certificado.seguimientos',
            'certificado.seguimientos.usuarioOrigen.funcionario.cargos',
            'certificado.seguimientos.usuarioOrigen.persona.empresa',
            'certificado.seguimientos.usuarioOrigen.persona.natural',
            'certificado.seguimientos.usuarioOrigen.roles',
            'certificado.seguimientos.usuarioSiguiente.funcionario.cargos',
            'certificado.seguimientos.usuarioSiguiente.persona.empresa',
            'certificado.seguimientos.usuarioSiguiente.persona.natural',
            'certificado.seguimientos.usuarioSiguiente.roles',
            // Producto/presentacion llegan por certificados_registros -> registros.
            'certificado.registros.producto.tipoProducto',
            'certificado.registros.producto.fabricante',
            'certificado.registros.presentacion',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Columnas por pantalla
    |--------------------------------------------------------------------------
    */

    public function columns(): array
    {
        return match ($this->bandeja) {
            'recibidas' => $this->columnasTramitesAtender(),
            'enviadas' => $this->columnasMisTramites(),
            'finalizados' => $this->columnasTramitesFinalizados(),
            default => $this->columnasConsultaGeneral(),
        };
    }

    // Tabla del solicitante: solo muestra los datos necesarios para consultar su tramite.
    private function columnasMisTramites(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            $this->columnaCodigoTramite(),
            $this->columnaTipoTramite(),
            $this->columnaBeneficiario(),
            $this->columnaTramitador(),
            $this->columnaFechaHora(),
            $this->columnaEstado(),
            $this->columnaAcciones(),
        ];
    }

    // Tabla usada por "Seguimiento de tramite".
    private function columnasConsultaGeneral(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            $this->columnaCodigoTramite(),
            $this->columnaTipoTramite(),
            $this->columnaBeneficiario(),
            $this->columnaTramitador(),
            $this->columnaUsuarioActualTramite(),
            $this->columnaFechaUsuarioActual(),
            $this->columnaEstado(),
            $this->columnaAcciones(),
        ];
    }

    // Tabla usada por "Tramites finalizados"; muestra primero los cierres mas recientes.
    private function columnasTramitesFinalizados(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            $this->columnaCodigoTramite(),
            $this->columnaTipoTramite(),
            $this->columnaBeneficiario(),
            $this->columnaTramitador(),
            $this->columnaFechasTramite(),
            $this->columnaUltimoUsuario(),
            $this->columnaRequisitosCumplidos(),
            $this->columnaEstado(),
            $this->columnaAcciones(),
        ];
    }

    // Tabla usada por "Tramites a atender". Se mantiene compacta para abrir rapido el detalle.
    private function columnasTramitesAtender(): array
    {
        return [
            Column::make('Id', 'id')->sortable(),
            $this->columnaCodigoTramite(),
            $this->columnaTipoTramite(),
            $this->columnaBeneficiario(),
            $this->columnaTramitador(),
            $this->columnaFecha(),
            $this->columnaDerivadoPor(),
            $this->columnaReferencia(),
            $this->columnaEstado(),
            $this->columnaAcciones(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Definicion de columnas reutilizables
    |--------------------------------------------------------------------------
    */

    private function columnaCodigoTramite(): Column
    {
        return Column::make('Codigo tramite', 'id_certificado')
            ->format(fn ($valor, $fila) => $fila->certificado?->codigo ?: 'Sin codigo')
            ->searchable(function (Builder $query, string $search) {
                $query->orWhereHas('certificado', function (Builder $consulta) use ($search) {
                    $consulta->where('codigo', 'like', '%' . $search . '%');
                });
            });
    }

    private function columnaTipoTramite(): Column
    {
        return Column::make('Tipo de tramite', 'id_certificado')
            ->format(fn ($valor, $fila) => $fila->certificado?->tipoCertificado?->nombre ?: 'Sin tipo')
            ->searchable(function (Builder $query, string $search) {
                $query->orWhereHas('certificado.tipoCertificado', function (Builder $consulta) use ($search) {
                    $consulta->where('nombre', 'like', '%' . $search . '%');
                });
            });
    }

    private function columnaBeneficiario(): Column
    {
        return Column::make('Beneficiario', 'id_certificado')
            ->format(fn ($valor, $fila) => $this->nombrePersona($fila->certificado?->beneficiario, 'Sin beneficiario'))
            ->searchable(function (Builder $query, string $search) {
                $this->buscarPersonaRelacionada($query, 'beneficiario', $search);
            });
    }

    private function columnaTramitador(): Column
    {
        return Column::make('Tramitador', 'id_certificado')
            ->format(fn ($valor, $fila) => $this->nombrePersona($fila->certificado?->tramitador, 'Sin tramitador'))
            ->searchable(function (Builder $query, string $search) {
                $this->buscarPersonaRelacionada($query, 'tramitador', $search);
            });
    }

    private function columnaFecha(): Column
    {
        return Column::make('Fecha', 'fecha_inicio')
            ->format(fn ($valor) => $valor ? Carbon::parse($valor)->format('d/m/Y') : 'Sin fecha')
            ->sortable();
    }

    private function columnaFechaHora(): Column
    {
        return Column::make('Fecha y hora', 'created_at')
            ->format(fn ($valor) => $valor ? Carbon::parse($valor)->format('d/m/Y H:i') : 'Sin fecha')
            ->sortable();
    }

    private function columnaFechasTramite(): Column
    {
        return Column::make('Inicio / finalizacion', 'fecha_inicio')
            ->label(fn ($fila) => $this->htmlFechasTramite($fila))
            ->html()
            ->sortable();
    }

    private function columnaUsuarioActualTramite(): Column
    {
        return Column::make('Usuario actual')
            ->label(fn ($fila) => $this->htmlUsuarioActualTramite($fila))
            ->html();
    }

    private function columnaFechaUsuarioActual(): Column
    {
        return Column::make('Desde')
            ->label(fn ($fila) => $this->fechaMovimientoActual($fila))
            ->sortable();
    }

    private function columnaProductoRegistro(): Column
    {
        return Column::make('Producto / Registro')
            ->label(fn ($fila) => $this->htmlProductoRegistro($fila))
            ->html();
    }

    private function columnaPresentacion(): Column
    {
        return Column::make('Presentacion')
            ->label(fn ($fila) => $this->textoPresentacion($fila));
    }

    private function columnaRequisitos(): Column
    {
        return Column::make('Requisitos')
            ->label(fn ($fila) => $this->textoResumenRequisitos($fila));
    }

    private function columnaRequisitosCumplidos(): Column
    {
        return Column::make('Requisitos cumplidos')
            ->label(fn ($fila) => $this->htmlRequisitosCumplidos($fila))
            ->html();
    }

    private function columnaUltimaEtapa(): Column
    {
        return Column::make('Ultima etapa')
            ->label(function ($fila) {
                $seguimiento = $this->ultimoMovimiento($fila);

                return $seguimiento?->descripcion_final ?: 'Sin seguimiento';
            });
    }

    private function columnaResponsableActual(): Column
    {
        return Column::make('Responsable actual')
            ->label(fn ($fila) => $this->htmlResponsableActual($fila))
            ->html();
    }

    private function columnaUltimoUsuario(): Column
    {
        return Column::make('Ultimo usuario')
            ->label(fn ($fila) => $this->htmlUltimoUsuario($fila))
            ->html();
    }

    private function columnaDerivadoPor(): Column
    {
        return Column::make('Derivado por')
            ->label(fn ($fila) => $this->htmlDerivadoPor($fila))
            ->html();
    }

    private function columnaReferencia(): Column
    {
        return Column::make('Referencia')
            ->label(function ($fila) {
                $referencia = $this->movimientoActivoParaUsuario($fila)?->referencia;

                return '<span class="block min-w-[180px] whitespace-normal text-sm font-semibold leading-5 text-slate-700">'
                    . e($referencia ?: 'Sin referencia')
                    . '</span>';
            })
            ->html();
    }

    private function columnaEstado(): Column
    {
        return Column::make('Estado', 'estado')
            ->format(function ($valor, $fila) {
                $estado = $fila->certificado?->estado ?? $valor;

                return $this->chipEstadoTramite($estado);
            })
            ->html()
            ->sortable();
    }

    private function columnaAcciones(): Column
    {
        return Column::make('Acciones')
            ->label(function ($fila) {
                if (!$fila->certificado) {
                    return '';
                }

                return view($this->vistaAccionesBandeja(), [
                    'seguimiento' => $fila,
                    'bandeja' => $this->bandeja,
                ]);
            })
            ->html();
    }

    /*
    |--------------------------------------------------------------------------
    | Vistas Blade para botones de accion
    |--------------------------------------------------------------------------
    */

    // Cada carpeta del modulo tiene su propio archivo acciones.blade.php.
    private function vistaAccionesBandeja(): string
    {
        return match ($this->bandeja) {
            'enviadas' => 'seguimientos_certificados.mis_tramites.acciones',
            'todos' => 'seguimientos_certificados.seguimiento_tramite.acciones',
            'finalizados' => 'seguimientos_certificados.tramites_finalizados.acciones',
            default => 'seguimientos_certificados.tramites_atender.acciones',
        };
    }

    /*
    |--------------------------------------------------------------------------
    | Filtros por bandeja
    |--------------------------------------------------------------------------
    */

    // Bandeja del solicitante: muestra tramites donde su usuario es beneficiario o tramitador.
    private function filtrarSolicitudesEnviadas(Builder $query): Builder
    {
        return $query->where(function (Builder $consulta) {
            $consulta
                ->whereHas('certificado.beneficiario', function (Builder $persona) {
                    $persona->where('id_usuario', auth()->id());
                })
                ->orWhereHas('certificado.tramitador', function (Builder $persona) {
                    $persona->where('id_usuario', auth()->id());
                });
        });
    }

    // Bandeja interna: cada funcionario ve solo tramites activos enviados a su usuario.
    private function filtrarSolicitudesRecibidas(Builder $query): Builder
    {
        return $query->whereHas('certificado.seguimientos', function (Builder $consulta) {
            $consulta->where('id_usuario_siguiente', auth()->id())
                ->whereNull('fecha_derivacion')
                ->where('estado', 'ACTIVO');
        });
    }

    // Consulta general: solo funcionarios autorizados pueden ver todos los tramites.
    private function filtrarTodosLosTramites(Builder $query): Builder
    {
        if (!$this->usuarioPuedeConsultarTodosLosTramites()) {
            return $query->whereRaw('1 = 0');
        }

        return $query->latest('id');
    }

    // Finalizados: solo tramites cerrados, ordenados por la fecha final mas reciente.
    private function filtrarTramitesFinalizados(Builder $query): Builder
    {
        if (!$this->usuarioPuedeConsultarTodosLosTramites()) {
            return $query->whereRaw('1 = 0');
        }

        return $query
            ->whereHas('certificado', function (Builder $consulta) {
                $consulta->whereIn('estado', ['APROBADO', 'EMITIDO'])
                    ->orWhereHas('seguimientos', fn (Builder $movimiento) => $movimiento->where('estado', 'FINALIZADO'));
            })
            ->orderByDesc(
                Certificado::query()
                    ->select('fecha_fin')
                    ->whereColumn('certificados.id', 'seguimientos.id_certificado')
                    ->limit(1)
            )
            ->latest('id');
    }

    private function usuarioPuedeConsultarTodosLosTramites(): bool
    {
        $usuario = auth()->user();
        $usuario?->loadMissing('funcionario.cargos');

        return (bool) $usuario
            && (
                $usuario->tieneRol('administrador')
                || $usuario->tieneRol('tecnico-evaluador')
                || $this->usuarioInternoActivo($usuario)
            );
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de presentacion
    |--------------------------------------------------------------------------
    */

    private function chipEstadoTramite(?string $estado): string
    {
        $clase = Certificado::claseEstadoCertificado($estado);
        $texto = Certificado::textoEstadoCertificado($estado);

        return '<span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-black leading-none ' . $clase . '">'
            . e($texto)
            . '</span>';
    }

    private function htmlProductoRegistro(Seguimiento $seguimiento): string
    {
        $registro = $this->registroPrincipal($seguimiento);

        if (!$registro) {
            return '<span class="text-slate-500">Sin producto asociado</span>';
        }

        $producto = $registro->producto;
        $nombreProducto = $producto?->nombre_comercial ?: 'Sin producto';
        $codigoRegistro = $registro->codigo_autorizacion ?: 'Sin registro';
        $tipoProducto = $producto?->tipoProducto?->descripcion ?: 'Sin tipo';

        return '<div class="min-w-[220px]">'
            . '<strong class="text-slate-800">' . e($nombreProducto) . '</strong>'
            . '<div class="text-xs font-semibold text-slate-500">Reg. ' . e($codigoRegistro) . ' | ' . e($tipoProducto) . '</div>'
            . '</div>';
    }

    private function textoPresentacion(Seguimiento $seguimiento): string
    {
        $presentacion = $this->registroPrincipal($seguimiento)?->presentacion;

        if (!$presentacion) {
            return 'Sin presentacion';
        }

        $cantidadUnidad = trim(($presentacion->cantidad ?? '') . ' ' . ($presentacion->unidad ?? ''));

        return $cantidadUnidad . ($presentacion->descripcion ? ' - ' . $presentacion->descripcion : '');
    }

    private function textoResumenRequisitos(Seguimiento $seguimiento): string
    {
        $requisitos = $seguimiento->certificado?->certificadoRequisitos;

        if (!$requisitos || $requisitos->isEmpty()) {
            return 'Sin requisitos';
        }

        $revisados = $requisitos->where('estado', '!=', 'PENDIENTE_REVISION')->count();

        return $revisados . '/' . $requisitos->count() . ' revisados';
    }

    private function htmlRequisitosCumplidos(Seguimiento $seguimiento): string
    {
        $requisitos = $seguimiento->certificado?->certificadoRequisitos;

        if (!$requisitos || $requisitos->isEmpty()) {
            return '<span class="text-slate-500">Sin requisitos</span>';
        }

        $cumplidos = $requisitos->where('cumple', 'SI')->count();
        $total = $requisitos->count();

        return '<span class="inline-flex items-center rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-black text-emerald-700">'
            . e($cumplidos . '/' . $total)
            . '</span>';
    }

    private function htmlFechasTramite(Seguimiento $seguimiento): string
    {
        $fechaInicio = $seguimiento->fecha_inicio ?: $seguimiento->created_at;
        $fechaFinal = $this->ultimoMovimiento($seguimiento)?->fecha_final
            ?: $seguimiento->certificado?->fecha_fin;

        return '<div class="min-w-[140px] text-sm leading-5">'
            . '<div><span class="font-black text-slate-700">Inicio:</span> ' . e($this->formatearFechaHora($fechaInicio)) . '</div>'
            . '<div class="text-slate-500"><span class="font-black text-slate-600">Fin:</span> ' . e($this->formatearFechaHora($fechaFinal)) . '</div>'
            . '</div>';
    }

    private function htmlResponsableActual(Seguimiento $seguimiento): string
    {
        return $this->htmlUsuarioFuncionario(
            $this->movimientoActualDelTramite($seguimiento)?->usuarioSiguiente,
            'Sin asignar'
        );
    }

    private function htmlUsuarioActualTramite(Seguimiento $seguimiento): string
    {
        $movimiento = $this->movimientoActualDelTramite($seguimiento);

        return $this->htmlUsuarioFuncionario(
            $movimiento?->usuarioSiguiente ?: $movimiento?->usuarioOrigen,
            'Sin usuario actual'
        );
    }

    private function htmlUltimoUsuario(Seguimiento $seguimiento): string
    {
        $ultimoMovimiento = $this->ultimoMovimiento($seguimiento);

        return $this->htmlUsuarioFuncionario(
            $ultimoMovimiento?->usuarioSiguiente ?: $ultimoMovimiento?->usuarioOrigen,
            'Sin usuario'
        );
    }

    private function htmlDerivadoPor(Seguimiento $seguimiento): string
    {
        return $this->htmlUsuarioFuncionario(
            $this->movimientoActivoParaUsuario($seguimiento)?->usuarioOrigen,
            'Sin origen'
        );
    }

    private function htmlUsuarioFuncionario($usuario, string $textoVacio): string
    {
        if (!$usuario) {
            return '<span class="text-slate-500">' . e($textoVacio) . '</span>';
        }

        $funcionario = $usuario->funcionario;
        $nombreFuncionario = $funcionario
            ? trim(implode(' ', array_filter([
                $funcionario->nombres,
                $funcionario->apellido_paterno,
                $funcionario->apellido_materno,
            ])))
            : '';

        if ($funcionario) {
            $cargo = $funcionario->cargos?->pluck('nombre')->filter()->first() ?: 'Sin cargo';

            return '<div class="min-w-[160px]">'
                . '<strong class="text-slate-800">' . e($nombreFuncionario ?: ($usuario->email ?: 'Sin funcionario')) . '</strong>'
                . '<div class="text-xs font-semibold text-slate-500">' . e($cargo) . '</div>'
                . '</div>';
        }

        $nombrePersona = $this->nombrePersona($usuario->persona, $usuario->name ?: 'Sin usuario');
        $detalleUsuario = $usuario->roles?->pluck('name')->filter()->unique()->implode(', ') ?: 'Sin rol';

        return '<div class="min-w-[160px]">'
            . '<strong class="text-slate-800">' . e($nombrePersona) . '</strong>'
            . '<div class="text-xs font-semibold text-slate-500">' . e($detalleUsuario) . '</div>'
            . '</div>';
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers de datos
    |--------------------------------------------------------------------------
    */

    private function ultimoMovimiento(Seguimiento $seguimiento): ?Seguimiento
    {
        return $seguimiento->certificado?->seguimientos?->sortByDesc('id')->first();
    }

    private function movimientoActualDelTramite(Seguimiento $seguimiento): ?Seguimiento
    {
        $movimientoActivo = $seguimiento->certificado?->seguimientos
            ?->filter(fn ($movimiento) => $movimiento->id_usuario_siguiente
                && !$movimiento->fecha_derivacion
                && $movimiento->estado === 'ACTIVO')
            ->sortByDesc('id')
            ->first();

        return $movimientoActivo ?: $this->ultimoMovimiento($seguimiento);
    }

    private function fechaMovimientoActual(Seguimiento $seguimiento): string
    {
        $movimiento = $this->movimientoActualDelTramite($seguimiento);

        return $this->formatearFechaHora($movimiento?->created_at ?: $movimiento?->fecha_inicio);
    }

    private function movimientoActivoParaUsuario(Seguimiento $seguimiento): ?Seguimiento
    {
        $movimientoActivo = $seguimiento->certificado?->seguimientos
            ?->filter(fn ($movimiento) => (int) $movimiento->id_usuario_siguiente === (int) auth()->id()
                && !$movimiento->fecha_derivacion
                && $movimiento->estado === 'ACTIVO')
            ->sortByDesc('id')
            ->first();

        return $movimientoActivo ?: $this->ultimoMovimiento($seguimiento);
    }

    private function registroPrincipal(Seguimiento $seguimiento)
    {
        return $seguimiento->certificado?->registros?->first();
    }

    private function formatearFechaHora($fecha): string
    {
        return $fecha ? Carbon::parse($fecha)->format('d/m/Y H:i') : 'Sin fecha';
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

    private function buscarPersonaRelacionada(Builder $query, string $relacion, string $search): void
    {
        $query->orWhereHas('certificado.' . $relacion . '.empresa', function (Builder $consulta) use ($search) {
            $consulta->where('razon_social', 'like', '%' . $search . '%');
        })
            ->orWhereHas('certificado.' . $relacion . '.natural', function (Builder $consulta) use ($search) {
                $consulta->where('nombres', 'like', '%' . $search . '%')
                    ->orWhere('apellido_paterno', 'like', '%' . $search . '%')
                    ->orWhere('apellido_materno', 'like', '%' . $search . '%');
            });
    }

    // Acepta los dos formatos usados en el sistema: 1 y ACTIVO.
    private function estadoActivo($estado): bool
    {
        return in_array((string) $estado, ['1', 'ACTIVO'], true);
    }

    // Reglas para identificar funcionarios internos que pueden consultar la bandeja general.
    private function usuarioInternoActivo($usuario): bool
    {
        return (bool) $usuario?->funcionario
            && $this->estadoActivo($usuario->funcionario->estado)
            && $usuario->funcionario->cargos->contains(fn ($cargo) => $this->estadoActivo($cargo->estado));
    }
}

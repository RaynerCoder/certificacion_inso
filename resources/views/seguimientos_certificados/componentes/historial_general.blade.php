@php
    /*
     * HISTORIAL GENERAL DEL TRAMITE
     * Este partial solo prepara datos para mostrar la hoja de ruta.
     * No guarda ni modifica registros; eso queda en SeguimientoController.
     */

    // =========================
    // 1) NOMBRES VISIBLES
    // =========================

    // Muestra razon social si es empresa o nombre completo si es persona natural.
    $nombrePersonaTimeline = function ($persona) {
        if (!$persona) {
            return 'No registrado';
        }

        if ($persona->empresa) {
            return $persona->empresa->razon_social ?: 'Empresa sin razon social';
        }

        if ($persona->natural) {
            $nombreCompleto = trim(implode(' ', array_filter([
                $persona->natural->nombres,
                $persona->natural->apellido_paterno,
                $persona->natural->apellido_materno,
            ])));

            return $nombreCompleto ?: 'Persona natural sin nombre';
        }

        return 'Persona #' . $persona->id;
    };

    // Identifica si la cuenta pertenece a una empresa, persona natural o funcionario interno.
    $tipoPersonaTimeline = function ($persona) {
        if (!$persona) {
            return 'Sin persona vinculada';
        }

        if ($persona->empresa) {
            return 'Empresa';
        }

        if ($persona->natural) {
            return 'Persona natural';
        }

        return 'Persona registrada';
    };

    // Determina la función real de la persona dentro de la empresa beneficiaria.
    $relacionesSolicitantesTimeline = [];
    $tipoRelacionSolicitanteTimeline = function ($personaSolicitante, $personaBeneficiaria) use (&$relacionesSolicitantesTimeline, $tipoPersonaTimeline) {
        $empresa = $personaBeneficiaria?->empresa;

        if (! $empresa) {
            return $tipoPersonaTimeline($personaSolicitante);
        }

        if (! $personaSolicitante) {
            return 'Relación no identificada';
        }

        $claveRelacion = $empresa->id . ':' . $personaSolicitante->id;
        if (isset($relacionesSolicitantesTimeline[$claveRelacion])) {
            return $relacionesSolicitantesTimeline[$claveRelacion];
        }

        $relaciones = \App\Models\Responsable::withTrashed()
            ->with('rol:id,slug')
            ->where('id_empresa', $empresa->id)
            ->where('id_persona', $personaSolicitante->id)
            ->whereHas('rol', fn ($rol) => $rol->whereIn('slug', ['solicitante', 'tramitador']))
            ->get();
        $roles = $relaciones
            ->filter(fn ($relacion) => ! $relacion->trashed() && in_array((string) $relacion->estado, ['1', 'ACTIVO'], true))
            ->pluck('rol.slug')
            ->filter()
            ->unique();

        $esRepresentante = $roles->contains('solicitante');
        $esTramitador = $roles->contains('tramitador');
        $rolesAnteriores = $relaciones
            ->filter(fn ($relacion) => $relacion->trashed() || filled($relacion->fecha_baja) || (string) $relacion->estado === 'INACTIVO')
            ->pluck('rol.slug');

        return $relacionesSolicitantesTimeline[$claveRelacion] = match (true) {
            $esRepresentante && $esTramitador => 'Representante legal y tramitador',
            $esRepresentante => 'Representante legal',
            $esTramitador => 'Tramitador',
            $rolesAnteriores->contains('tramitador') => 'Ex-Tramitador',
            $rolesAnteriores->contains('solicitante') => 'Ex-representante legal',
            default => 'Relación con la empresa no identificada',
        };
    };

    // Obtiene el nombre visible del usuario sin mostrar correo cuando existe persona vinculada.
    $usuarioTimeline = function ($usuario, string $fallback = 'No registrado', string $cargoFallback = 'Sin cargo') use ($nombrePersonaTimeline, $tipoRelacionSolicitanteTimeline, $certificado) {
        if (!$usuario) {
            return [
                'nombre' => $fallback,
                'cargo' => $cargoFallback,
                'busqueda' => strtolower($fallback . ' ' . $cargoFallback),
            ];
        }

        $usuario->loadMissing('funcionario.cargos', 'persona.empresa', 'persona.natural');

        $funcionario = $usuario->funcionario;
        $nombreFuncionario = $funcionario
            ? trim(implode(' ', array_filter([
                $funcionario->nombres,
                $funcionario->apellido_paterno,
                $funcionario->apellido_materno,
            ])))
            : '';

        $cargos = $funcionario?->cargos?->pluck('nombre')->filter()->implode(', ');

        if ($nombreFuncionario) {
            $nombre = $nombreFuncionario;
            $cargo = $cargos ?: $cargoFallback;
        } elseif ($usuario->persona) {
            $nombre = $nombrePersonaTimeline($usuario->persona);
            $cargo = $tipoRelacionSolicitanteTimeline($usuario->persona, $certificado->beneficiario);
        } else {
            $nombre = $usuario->name ?: $fallback;
            $cargo = 'Sin persona vinculada';
        }

        return [
            'nombre' => $nombre,
            'cargo' => $cargo,
            'busqueda' => strtolower($nombre . ' ' . $cargo . ' ' . ($usuario->email ?? '')),
        ];
    };

    // =========================
    // 2) FORMATEADORES CORTOS
    // =========================

    // Evita celdas vacias con fallbacks cortos, sin agregar frases de relleno.
    $datoTimeline = function ($valor, string $fallback = 'No registrado') {
        $texto = trim((string) $valor);

        return $texto !== '' ? $texto : $fallback;
    };

    // Los movimientos muestran fecha y hora; los registros antiguos conservan su valor disponible.
    $fechaTimeline = function ($fecha) {
        if (!$fecha) {
            return 'Sin fecha';
        }

        return \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y H:i');
    };

    // =========================
    // 3) ESTADOS VISUALES
    // =========================

    // Respeta el estado guardado y solo especializa movimientos operativos explícitos.
    $estadoMovimientoTimeline = function ($seguimiento) {
        return $seguimiento->estadoVisualHistorial();
    };

    // Estado principal del tramite en formato chip, separado del estado de cada movimiento.
    $estadoCertificadoTimeline = function (?string $estado) {
        $clase = match ($estado) {
            'APROBADO', 'EMITIDO', 'ACTIVO' => 'is-success',
            'OBSERVADO' => 'is-warning',
            'EN_REVISION' => 'is-info',
            'VENCIDO', 'ANULADO', 'RECHAZADO' => 'is-danger',
            default => 'is-neutral',
        };

        return [
            'label' => \App\Models\Certificado::textoEstadoCertificado($estado),
            'class' => $clase,
        ];
    };

    // =========================
    // 4) DATOS BASE DEL RESUMEN
    // =========================

    $estadoActualTimeline = $estadoCertificadoTimeline($certificado->estado);

    $seguimientosOrdenadosTimeline = $certificado->seguimientos->sortBy('id');
    $primerSeguimientoTimeline = $seguimientosOrdenadosTimeline->first();
    $gestorActualTimeline = app(\App\Services\GestionTramitadoresService::class)->gestorActual($certificado);
    $personaSolicitanteTimeline = $gestorActualTimeline['persona'];
    $nombreSolicitanteTimeline = $nombrePersonaTimeline($personaSolicitanteTimeline);
    $tipoSolicitanteTimeline = $gestorActualTimeline['tipo'];
    $fechaOrigenTimeline = $certificado->fecha_inicio
        ?: ($primerSeguimientoTimeline?->fecha_inicio ? \Illuminate\Support\Carbon::parse($primerSeguimientoTimeline->fecha_inicio) : null);
@endphp
@include('seguimientos_certificados.estilos.historial_general')

<div class="ruta-simple" data-hoja-ruta>
    {{-- RESUMEN SUPERIOR: datos generales del tramite sin repetir toda la revision. --}}
    <section class="ruta-contexto" aria-label="Resumen del trámite">
        <article class="ruta-contexto-item">
            <span class="ruta-label">Código</span>
            <span class="ruta-valor">{{ $datoTimeline($certificado->codigo, 'Sin código') }}</span>
        </article>

        <article class="ruta-contexto-item">
            <span class="ruta-label">Tipo de trámite</span>
            <span class="ruta-valor">{{ $certificado->tipoCertificado?->nombre ?? 'Sin tipo registrado' }}</span>
        </article>

        <article class="ruta-contexto-item">
            <span class="ruta-label">Gestionado por</span>
            <span class="ruta-valor">{{ $nombreSolicitanteTimeline }}</span>
            <span class="ruta-rol">{{ $tipoSolicitanteTimeline }}</span>
        </article>

        <article class="ruta-contexto-item">
            <span class="ruta-label">Beneficiario</span>
            <span class="ruta-valor">{{ $nombrePersonaTimeline($certificado->beneficiario) }}</span>
        </article>

        <article class="ruta-contexto-item">
            <span class="ruta-label">Estado actual</span>
            <span class="ruta-chip {{ $estadoActualTimeline['class'] }}">
                {{ $estadoActualTimeline['label'] }}
            </span>
        </article>

        <article class="ruta-contexto-item">
            <span class="ruta-label">Fecha de solicitud</span>
            <span class="ruta-valor">{{ $fechaTimeline($fechaOrigenTimeline) }}</span>
        </article>
    </section>

    {{-- FILTROS: solo filtran en pantalla, no consultan nuevamente la base de datos. --}}
    <section class="ruta-toolbar" aria-label="Filtros de seguimiento">
        <div>
            <h2 class="ruta-toolbar-title">Movimientos del trámite</h2>
        </div>

        <div class="ruta-toolbar-actions">
            <label class="ruta-control is-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="search" placeholder="Buscar persona, referencia o descripción" data-ruta-search>
            </label>

            <label class="ruta-control">
                <i class="fa-solid fa-filter"></i>
                <select data-ruta-status>
                    <option value="">Todos los estados</option>
                    <option value="activo">Activo</option>
                    <option value="observado">Observado</option>
                    <option value="finalizado">Finalizado</option>
                </select>
            </label>
        </div>
    </section>

    {{-- TABLA DE MOVIMIENTOS: cada fila corresponde a un registro real de seguimientos. --}}
    <section class="ruta-table-wrap" aria-label="Tabla de movimientos del trámite">
        <table class="ruta-table">
            <colgroup>
                <col class="ruta-col-id">
                <col class="ruta-col-fecha">
                <col class="ruta-col-persona">
                <col class="ruta-col-persona">
                <col class="ruta-col-anterior">
                <col class="ruta-col-referencia">
                <col class="ruta-col-descripcion">
            </colgroup>
            <thead>
                <tr>
                    <th>N.º / ID</th>
                    <th>Fecha del movimiento</th>
                    <th>Quién envía</th>
                    <th>Quién recibe</th>
                    <th>Responsable anterior</th>
                    <th>Referencias internas del INSO</th>
                    <th>Descripción final</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($seguimientosOrdenadosTimeline as $seguimiento)
                    @php
                        // Mapeo directo a la tabla seguimientos:
                        // id_usuario_origen    = quien registra/envia el movimiento.
                        // id_usuario_siguiente = quien recibe o atiende la siguiente etapa.
                        // id_usuario_anterior  = responsable que tenia antes el tramite.
                        $estadoVisual = $estadoMovimientoTimeline($seguimiento);
                        $quienEnvia = $usuarioTimeline($seguimiento->usuarioOrigen, 'Sin origen', 'Sin cargo');
                        $quienRecibe = $usuarioTimeline($seguimiento->usuarioSiguiente, 'Sin destino', 'Sin cargo');
                        $responsableAnterior = $usuarioTimeline(
                            $seguimiento->usuarioAnterior,
                            $loop->first ? 'Sin anterior' : 'Sin responsable anterior',
                            'Sin cargo'
                        );
                        // fecha_inicio registra cuándo comenzó el movimiento; created_at cubre datos antiguos.
                        $fechaMovimiento = $seguimiento->fecha_inicio ?: $seguimiento->created_at;
                        $estadoFiltro = strtolower($estadoVisual['filter']);
                        // Cada movimiento muestra su referencia y la descripción registrada al cerrarlo.
                        $referencia = $datoTimeline($seguimiento->referencia, 'Sin referencia');
                        $descripcionFinal = trim((string) $seguimiento->descripcion_final);
                        $esTransferenciaPorBaja = str_contains(mb_strtolower($descripcionFinal), 'baja del tramitador');
                        $textoBusqueda = strtolower(implode(' ', array_filter([
                            $seguimiento->id,
                            $certificado->codigo,
                            $referencia,
                            $descripcionFinal,
                            $seguimiento->estado,
                            $quienEnvia['busqueda'],
                            $quienRecibe['busqueda'],
                            $responsableAnterior['busqueda'],
                        ])));
                    @endphp

                    <tr class="{{ $estadoVisual['class'] }}"
                        data-ruta-row
                        data-status="{{ $estadoFiltro }}"
                        data-search="{{ $textoBusqueda }}">
                        <td>
                            <span class="ruta-movimiento-num">{{ $loop->iteration }}</span>
                            <span class="ruta-movimiento-id">ID {{ $seguimiento->id }}</span>
                            <span class="ruta-chip {{ $estadoVisual['class'] }}">{{ $estadoVisual['label'] }}</span>
                        </td>

                        <td>
                            @if ($fechaMovimiento)
                                <time datetime="{{ \Illuminate\Support\Carbon::parse($fechaMovimiento)->toIso8601String() }}"
                                    style="display:inline-flex;flex-direction:column;gap:3px;white-space:nowrap;font-variant-numeric:tabular-nums">
                                    <span class="ruta-valor">{{ \Illuminate\Support\Carbon::parse($fechaMovimiento)->format('H:i') }}</span>
                                    <span>{{ \Illuminate\Support\Carbon::parse($fechaMovimiento)->format('d/m/Y') }}</span>
                                </time>
                            @else
                                <span class="ruta-valor">Sin fecha</span>
                            @endif
                        </td>

                        <td>
                            <div class="ruta-persona">
                                <span class="ruta-persona-nombre">{{ $quienEnvia['nombre'] }}</span>
                                <span class="ruta-persona-cargo">{{ $quienEnvia['cargo'] }}</span>
                                @if ($esTransferenciaPorBaja)
                                    <span class="ruta-persona-cargo" style="color:#0f766e">Registró la baja</span>
                                @endif
                            </div>
                        </td>

                        <td>
                            <div class="ruta-persona">
                                <span class="ruta-persona-nombre">{{ $quienRecibe['nombre'] }}</span>
                                <span class="ruta-persona-cargo">{{ $quienRecibe['cargo'] }}</span>
                                @if ($esTransferenciaPorBaja)
                                    <span class="ruta-persona-cargo" style="color:#0f766e">Recibe las correcciones pendientes</span>
                                @endif
                            </div>
                        </td>

                        <td>
                            <div class="ruta-persona">
                                <span class="ruta-persona-nombre">{{ $responsableAnterior['nombre'] }}</span>
                                <span class="ruta-persona-cargo">{{ $responsableAnterior['cargo'] }}</span>
                            </div>
                        </td>

                        <td>
                            <div class="ruta-texto {{ $seguimiento->referencia ? '' : 'ruta-dato-faltante' }}">
                                {{ $referencia }}
                            </div>
                        </td>

                        <td>
                            <div class="ruta-texto {{ $descripcionFinal !== '' ? '' : 'ruta-dato-faltante' }}" style="white-space: pre-line">{{ $descripcionFinal !== '' ? $descripcionFinal : '—' }}</div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="ruta-empty is-visible">Sin movimientos</div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="ruta-empty" data-ruta-empty>Sin movimientos</div>
    </section>
</div>
@include('seguimientos_certificados.componentes.historial_general_script')

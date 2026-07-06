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

    // Obtiene el nombre visible del usuario sin mostrar correo cuando existe persona vinculada.
    $usuarioTimeline = function ($usuario, string $fallback = 'No registrado', string $cargoFallback = 'Sin cargo') use ($nombrePersonaTimeline, $tipoPersonaTimeline) {
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
            $cargo = $tipoPersonaTimeline($usuario->persona);
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

    // Formatea fechas sin inventar datos cuando la fecha no existe.
    $fechaTimeline = function ($fecha) {
        if (!$fecha) {
            return 'Sin fecha';
        }

        $fechaTexto = (string) $fecha;
        $formato = strlen($fechaTexto) <= 10 ? 'd/m/Y' : 'd/m/Y H:i';

        return \Illuminate\Support\Carbon::parse($fecha)->format($formato);
    };

    // =========================
    // 3) ESTADOS VISUALES
    // =========================

    // Traduce el estado/movimiento a etiquetas claras para la hoja de ruta.
    $estadoMovimientoTimeline = function ($seguimiento) {
        $texto = strtolower(($seguimiento->estado ?? '') . ' ' . ($seguimiento->descripcion_final ?? '') . ' ' . ($seguimiento->referencia ?? ''));

        if (str_contains($texto, 'observ')) {
            return ['label' => 'Observado', 'class' => 'is-danger', 'filter' => 'observado'];
        }

        if (str_contains($texto, 'devuelto') || str_contains($texto, 'correccion') || str_contains($texto, 'corregir')) {
            return ['label' => 'Devuelto', 'class' => 'is-warning', 'filter' => 'devuelto'];
        }

        if (str_contains($texto, 'deriv') || str_contains($texto, 'asign')) {
            return ['label' => 'Derivado', 'class' => 'is-info', 'filter' => 'derivado'];
        }

        if (str_contains($texto, 'recib')) {
            return ['label' => 'Recibido', 'class' => 'is-success', 'filter' => 'recibido'];
        }

        return [
            'label' => ucfirst(strtolower($seguimiento->estado ?: 'Movimiento')),
            'class' => 'is-neutral',
            'filter' => strtolower($seguimiento->estado ?: 'movimiento'),
        ];
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
    $personaSolicitanteTimeline = $certificado->tramitador ?: $certificado->beneficiario;
    $nombreSolicitanteTimeline = $nombrePersonaTimeline($personaSolicitanteTimeline);
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
            <span class="ruta-label">Solicitante</span>
            <span class="ruta-valor">{{ $nombreSolicitanteTimeline }}</span>
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
            <span class="ruta-label">Fecha de inicio</span>
            <span class="ruta-valor">{{ $fechaTimeline($fechaOrigenTimeline) }}</span>
        </article>
    </section>

    {{-- FILTROS: solo filtran en pantalla, no consultan nuevamente la base de datos. --}}
    <section class="ruta-toolbar" aria-label="Filtros de seguimiento">
        <div>
            <h2 class="ruta-toolbar-title">Movimientos del trámite</h2>
            <span class="ruta-mini">{{ $seguimientosOrdenadosTimeline->count() }} registros de seguimiento</span>
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
                    <option value="recibido">Recibido</option>
                    <option value="derivado">Derivado</option>
                    <option value="observado">Observado</option>
                    <option value="devuelto">Devuelto</option>
                </select>
            </label>
        </div>
    </section>

    {{-- TABLA DE MOVIMIENTOS: cada fila corresponde a un registro real de seguimientos. --}}
    <section class="ruta-table-wrap" aria-label="Tabla de movimientos del trámite">
        <table class="ruta-table">
            <thead>
                <tr>
                    <th>Movimiento</th>
                    <th>Fecha</th>
                    <th>Quién envía</th>
                    <th>Quién recibe</th>
                    <th>Responsable anterior</th>
                    <th>Referencia</th>
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
                        $fechaPrincipal = $seguimiento->fecha_derivacion ?: $seguimiento->fecha_inicio ?: $seguimiento->created_at;
                        $estadoFiltro = strtolower($estadoVisual['filter']);
                        // Columnas reales de seguimientos: referencia y descripcion_final.
                        $referencia = $datoTimeline($seguimiento->referencia, 'Sin referencia');
                        $descripcionFinal = $datoTimeline($seguimiento->descripcion_final, 'Sin descripción');
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
                            <span class="ruta-movimiento-num">#{{ $loop->iteration }}</span>
                            <span class="ruta-chip {{ $estadoVisual['class'] }}">{{ $estadoVisual['label'] }}</span>
                            <span class="ruta-mini">Seguimiento #{{ $seguimiento->id }}</span>
                        </td>

                        <td>
                            <span class="ruta-valor">{{ $fechaTimeline($fechaPrincipal) }}</span>
                            <span class="ruta-mini">Inicio: {{ $fechaTimeline($seguimiento->fecha_inicio) }}</span>
                            @if ($seguimiento->fecha_final)
                                <span class="ruta-mini">Cierre: {{ $fechaTimeline($seguimiento->fecha_final) }}</span>
                            @endif
                        </td>

                        <td>
                            <div class="ruta-persona">
                                <span class="ruta-persona-nombre">{{ $quienEnvia['nombre'] }}</span>
                                <span class="ruta-persona-cargo">{{ $quienEnvia['cargo'] }}</span>
                            </div>
                        </td>

                        <td>
                            <div class="ruta-persona">
                                <span class="ruta-persona-nombre">{{ $quienRecibe['nombre'] }}</span>
                                <span class="ruta-persona-cargo">{{ $quienRecibe['cargo'] }}</span>
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
                            <div class="ruta-texto {{ $seguimiento->descripcion_final ? '' : 'ruta-dato-faltante' }}">
                                {{ $descripcionFinal }}
                            </div>
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

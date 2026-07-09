    @php
        // Devuelve razon social para empresa o nombre completo para persona natural.
        $nombrePersona = function ($persona) {
            if (!$persona) {
                return 'Sin persona';
            }

            if ($persona->empresa) {
                return $persona->empresa->razon_social;
            }

            if ($persona->natural) {
                return trim(
                    implode(
                        ' ',
                        array_filter([
                            $persona->natural->nombres,
                            $persona->natural->apellido_paterno,
                            $persona->natural->apellido_materno,
                        ]),
                    ),
                );
            }

            return 'Persona #' . $persona->id;
        };

        // Identifica si la persona relacionada es empresa o natural.
        $tipoPersona = function ($persona) {
            return $persona?->empresa ? 'Empresa' : 'Persona natural';
        };

        // Muestra CI cuando es persona natural y NIT cuando es empresa.
        $identificacionPersona = function ($persona) {
            if (!$persona) {
                return 'Sin dato';
            }

            if ($persona->empresa) {
                return $persona->nit ?: 'Sin NIT';
            }

            return $persona->natural?->ci ?: ($persona->nit ?: 'Sin CI/NIT');
        };

        // Toma el primer telefono registrado para mantener el resumen compacto.
        $telefonoPersona = function ($persona) {
            return $persona?->telefonos?->first()?->numero ?? 'Sin teléfono';
        };

        // Devuelve nombre completo de funcionario; evita mostrar el usuario corto de acceso.
        $nombreUsuario = function ($usuario, string $fallback = 'Sin usuario') {
            if (!$usuario) {
                return $fallback;
            }

            $usuario->loadMissing('funcionario');

            $funcionario = $usuario->funcionario;

            if ($funcionario) {
                $nombreCompleto = trim(implode(' ', array_filter([
                    $funcionario->nombres,
                    $funcionario->apellido_paterno,
                    $funcionario->apellido_materno,
                ])));

                if ($nombreCompleto !== '') {
                    return $nombreCompleto;
                }
            }

            return $usuario->email ?: $fallback;
        };

        // Devuelve los cargos reales del funcionario relacionado al usuario.
        // Si el usuario no es funcionario o no tiene cargo cargado, no inventa datos.
        $cargoUsuario = function ($usuario, string $fallback = 'Sin cargo') {
            if (!$usuario) {
                return $fallback;
            }

            $usuario->loadMissing('funcionario.cargos');

            $cargos = $usuario->funcionario?->cargos?->pluck('nombre')->filter()->implode(', ');

            return $cargos ?: $fallback;
        };

        // Normaliza enlaces de archivos guardados en storage o URLs externas.
        $urlArchivo = function (?string $ruta) {
            if (!$ruta) {
                return null;
            }

            if (\Illuminate\Support\Str::startsWith($ruta, ['http://', 'https://'])) {
                return $ruta;
            }

            return \Illuminate\Support\Str::startsWith($ruta, 'storage/')
                ? asset($ruta)
                : asset('storage/' . $ruta);
        };

        // Formatea fechas de tablas compactas y evita repetir Carbon en cada celda.
        $fechaCorta = function ($fecha, string $fallback = 'Sin fecha') {
            return $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') : $fallback;
        };

        // Muestra el texto legible del tipo de pago guardado en la base de datos.
        $textoTipoPago = function (?string $tipoPago) {
            return \App\Models\Pago::TIPOS_PAGOS[$tipoPago] ?? ($tipoPago ?: 'Sin tipo');
        };

        // Traduce el estado tecnico del requisito sin mostrar codigos crudos de base de datos.
        $textoEstadoRequisito = function (?string $estado) {
            return match ($estado) {
                'PENDIENTE_REVISION' => 'Pendiente de revisión',
                'REVISION_OBSERVADA' => 'Revisión observada',
                'OBSERVADO' => 'Observado',
                'APROBADO', 'CUMPLE' => 'Cumple',
                default => $estado ? str_replace('_', ' ', $estado) : 'Sin estado',
            };
        };

        $claseEstadoRequisito = function (?string $estado) {
            return match ($estado) {
                'APROBADO', 'CUMPLE', 'ACTIVO' => 'tramite-pill-ok',
                'OBSERVADO', 'REVISION_OBSERVADA' => 'tramite-pill-danger',
                default => 'tramite-pill-warn',
            };
        };

        // Prepara el enlace del PDF guardado, sea URL externa o archivo del storage.
        $documentoUrl = null;

        if ($certificado->url_documento) {
            $documentoUrl = $urlArchivo($certificado->url_documento);
        }

        // Calcula el avance de requisitos para mostrar una lectura rapida.
        $totalRequisitos = $certificado->certificadoRequisitos->count();
        $requisitosCumplidos = $certificado->certificadoRequisitos->where('cumple', 'SI')->count();
        $requisitosPendientes = $certificado->certificadoRequisitos->where('estado', 'PENDIENTE_REVISION')->count();
        $porcentajeRequisitos = $totalRequisitos > 0 ? round(($requisitosCumplidos / $totalRequisitos) * 100) : 0;

        // Clase visual del estado principal del certificado.
        $estadoClaseShow = match ($certificado->estado) {
            'APROBADO', 'EMITIDO' => 'cert-show-badge-ok',
            'VENCIDO', 'ANULADO', 'RECHAZADO' => 'cert-show-badge-danger',
            'OBSERVADO' => 'cert-show-badge-warning',
            'EN_REVISION' => 'cert-show-badge-info',
            default => 'cert-show-badge-neutral',
        };
        $estadoTextoShow = \App\Models\Certificado::textoEstadoCertificado($certificado->estado);
        $estadoIconoShow = \App\Models\Certificado::iconoEstadoCertificado($certificado->estado);

        // Toma la ultima evidencia de archivo del requisito: PDF o imagen.
        $evidenciaArchivoRequisito = function ($requisitoCertificado) {
            return $requisitoCertificado->evidenciasRequisitos
                ->filter(fn ($evidencia) => in_array($evidencia->tipoEvidencia?->codigo, ['PDF', 'IMAGEN'], true))
                ->sortByDesc('id')
                ->first();
        };

        $evidenciaPrincipalRequisito = function ($requisitoCertificado) {
            return $requisitoCertificado->evidenciasRequisitos
                ->sortByDesc('id')
                ->first();
        };

        $codigoEvidenciaRequisito = function ($requisitoCertificado) use ($evidenciaPrincipalRequisito) {
            return strtoupper((string) ($evidenciaPrincipalRequisito($requisitoCertificado)?->tipoEvidencia?->codigo ?? 'SIN_EVIDENCIA'));
        };

        $descripcionEvidenciaRequisito = function ($requisitoCertificado) use ($evidenciaPrincipalRequisito) {
            return $evidenciaPrincipalRequisito($requisitoCertificado)?->tipoEvidencia?->descripcion
                ?: 'Sin descripción registrada.';
        };
        $iconoEvidenciaRequisito = function (string $codigo) {
            return match (strtoupper($codigo)) {
                'PDF' => 'fa-regular fa-file-pdf',
                'IMAGEN' => 'fa-regular fa-image',
                'PAGO' => 'fa-solid fa-credit-card',
                'PRODUCTO' => 'fa-solid fa-box',
                'CERTIFICADO' => 'fa-regular fa-file-lines',
                'TEXTO' => 'fa-regular fa-keyboard',
                'PRESENCIAL' => 'fa-solid fa-person',
                default => 'fa-regular fa-file',
            };
        };

        $textoEvidenciaRequisito = function (string $codigo, bool $observado = false) {
            return match (strtoupper($codigo)) {
                'PDF' => $observado ? 'PDF observado' : 'Ver PDF',
                'IMAGEN' => $observado ? 'Imagen observada' : 'Ver imagen',
                'PAGO' => 'Ver comprobante',
                default => 'Ver evidencia',
            };
        };

        // Revisa el tipo de evidencia configurado para una fila de requisito.
        $requisitoTieneEvidencia = function ($requisitoCertificado, string|array $codigos) {
            $codigos = collect((array) $codigos)
                ->map(fn ($codigo) => strtoupper(trim((string) $codigo)))
                ->filter()
                ->values();

            return $codigos->isNotEmpty()
                && $requisitoCertificado->evidenciasRequisitos->contains(function ($evidencia) use ($codigos) {
                    return $codigos->contains(strtoupper((string) $evidencia->tipoEvidencia?->codigo));
                });
        };

        // Toma la ultima revision tecnica registrada para este requisito.
        $ultimaRevisionRequisito = function ($requisitoCertificado) {
            return $requisitoCertificado->revisionesRequisitos
                ->sortByDesc('id')
                ->first();
        };

        // Obtiene observaciones desde la relacion real: requisito -> revisiones -> observaciones.
        $observacionesDeRequisito = function ($requisitoCertificado) {
            return $requisitoCertificado->revisionesRequisitos
                ->flatMap(function ($revision) {
                    return $revision->observacionesRequisitos->map(function ($observacion) use ($revision) {
                        $observacion->setRelation('revisionRequisito', $revision);

                        return $observacion;
                    });
                })
                ->values();
        };

        // Ultima observacion del requisito, usada en tablas y correcciones.
        $ultimaObservacionDeRequisito = function ($requisitoCertificado) use ($observacionesDeRequisito) {
            return $observacionesDeRequisito($requisitoCertificado)->sortByDesc('id')->first();
        };

        // Localiza el PDF o imagen de cada requisito usando evidencias_requisitos.
        $urlDocumentoRequisito = function ($requisitoCertificado) {
            $evidencia = $requisitoCertificado->evidenciasRequisitos
                ->filter(fn ($item) => in_array($item->tipoEvidencia?->codigo, ['PDF', 'IMAGEN'], true))
                ->sortByDesc('id')
                ->first();

            if ($evidencia?->valor) {
                return \Illuminate\Support\Str::startsWith($evidencia->valor, ['http://', 'https://'])
                    ? $evidencia->valor
                    : asset($evidencia->valor);
            }

            $rutaPublica = 'storage/documentos/requisitos_certificados/' . $requisitoCertificado->id . '/documento.pdf';

            return file_exists(public_path($rutaPublica)) ? asset($rutaPublica) : null;
        };

        // Un trámite puede incluir varios productos. Se agrupan por producto para no repetir la misma ficha.
        $registrosPorProducto = $certificado->registros->groupBy(
            fn ($registro) => $registro->producto?->id ? 'producto_' . $registro->producto->id : 'registro_' . $registro->id,
        );
        // Historial por requisito: usa revisiones_requisitos y observaciones_requisitos.
        $historialRequisitos = $certificado->certificadoRequisitos->mapWithKeys(function ($requisitoCertificado) use ($observacionesDeRequisito, $ultimaRevisionRequisito, $nombreUsuario, $cargoUsuario) {
            $items = collect();

            $observacionesDeRequisito($requisitoCertificado)
                ->sortBy('id')
                ->each(function ($observacion) use ($items, $nombreUsuario, $cargoUsuario) {
                    $revisor = $observacion->revisionRequisito?->usuarioRevisor;

                    $items->push([
                        'tipo' => 'Observación técnica',
                        'estado' => 'danger',
                        'fecha' => $observacion->created_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                        'usuario' => $nombreUsuario($revisor, 'Sin revisor'),
                        'cargo' => $cargoUsuario($revisor),
                        'texto' => $observacion->observacion,
                    ]);
                });

            $ultimaRevision = $ultimaRevisionRequisito($requisitoCertificado);

            if ($requisitoCertificado->cumple === 'SI') {
                $items->push([
                    'tipo' => 'Cumple',
                    'estado' => 'success',
                    'fecha' => $requisitoCertificado->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'usuario' => $nombreUsuario($ultimaRevision?->usuarioRevisor, 'Sin revisor'),
                    'cargo' => $cargoUsuario($ultimaRevision?->usuarioRevisor),
                    'texto' => 'Sin observación',
                ]);
            }

            if ($requisitoCertificado->estado === 'OBSERVADO' && $items->isEmpty()) {
                $items->push([
                    'tipo' => 'Observado',
                    'estado' => 'warning',
                    'fecha' => $requisitoCertificado->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'usuario' => $nombreUsuario($ultimaRevision?->usuarioRevisor, 'Sin revisor'),
                    'cargo' => $cargoUsuario($ultimaRevision?->usuarioRevisor),
                    'texto' => 'Sin observación',
                ]);
            }

            if ($requisitoCertificado->estado === 'PENDIENTE_REVISION' && $items->isNotEmpty()) {
                $items->push([
                    'tipo' => 'Pendiente',
                    'estado' => 'neutral',
                    'fecha' => $requisitoCertificado->updated_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                    'usuario' => 'Sin usuario',
                    'cargo' => '',
                    'texto' => 'Sin observación',
                ]);
            }

            if ($items->isEmpty()) {
                $items->push([
                    'tipo' => 'Sin historial',
                    'estado' => 'neutral',
                    'fecha' => 'Sin fecha',
                    'usuario' => 'Sin usuario',
                    'cargo' => '',
                    'texto' => 'Sin observación',
                ]);
            }

            return [
                $requisitoCertificado->id => [
                    'titulo' => $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado',
                    'items' => $items->values(),
                ],
            ];
        });

        // Pasos visuales del detalle/seguimiento. No guardan datos; solo orientan al usuario.
        $pasosSeguimiento = [
            ['nombre' => 'Solicitud', 'activo' => true],
            ['nombre' => 'Documentos', 'activo' => $totalRequisitos > 0],
            ['nombre' => 'Revisión técnica', 'activo' => in_array($certificado->estado, ['EN_REVISION', 'OBSERVADO', 'APROBADO', 'EMITIDO'], true)],
            ['nombre' => 'Seguimiento', 'activo' => true],
        ];

        // Responsable actual: se toma del ultimo seguimiento activo para explicar quien tiene el tramite ahora.
        $seguimientoActualDetalle = $certificado->seguimientos->where('estado', 'ACTIVO')->sortByDesc('id')->first()
            ?? $certificado->seguimientos->sortByDesc('id')->first();
        // Primer movimiento del tramite: ayuda a mostrar como se inicio y quien lo registro.
        $seguimientoOrigenDetalle = $certificado->seguimientos->sortBy('id')->first();
        $responsableActualDetalle = $nombreUsuario(
            $seguimientoActualDetalle?->usuarioSiguiente,
            $esSolicitante ? 'Solicitante' : 'Sin responsable'
        );
        $responsableActualCargoDetalle = $seguimientoActualDetalle?->usuarioSiguiente
            ? $cargoUsuario($seguimientoActualDetalle->usuarioSiguiente)
            : '';
        // Estas banderas salen de la configuracion real del tipo de certificado.
        $requiereProductoTramite = $certificado->requiereEvidencia('PRODUCTO');
        $requierePagoTramite = $certificado->requiereEvidencia('PAGO');
        // Pago del tramite: si ya existe, se muestra como registrado y no se permite modificar desde requisitos.
        $pagoPrincipalTramite = $certificado->pagos->first();
        $tienePagoRegistrado = filled($pagoPrincipalTramite?->id);
        $puedeRegistrarPago = $requierePagoTramite
            && !$tienePagoRegistrado
            && !$esSolicitante
            && ($puedeAsignarTecnico || $puedeRevisarRequisitos);
        $abrirModalPago = $puedeRegistrarPago
            && collect([
                'form_id_procedencia_pago',
                'form_tipo_pago',
                'form_fecha_pago',
                'form_monto_pago',
                'form_comprobante_pago',
                'form_id_certificado',
            ])->contains(fn ($campo) => $errors->has($campo));

        // Accion tecnica: permite registrar productos para el importador/beneficiario del tramite.
        $puedeRegistrarProductoTramite = $requiereProductoTramite
            && !$esSolicitante
            && ($puedeAsignarTecnico || $puedeRevisarRequisitos)
            && filled($certificado->beneficiario?->id)
            && \Illuminate\Support\Facades\Route::has('productos_create');
        $urlRetornoProductoTramite = route('certificados_show', [
            'certificado' => $certificado,
            'bandeja' => request('bandeja', 'recibidas'),
        ]);
        $urlRegistrarProductoTramite = $puedeRegistrarProductoTramite
            ? route('productos_create', [
                'form_id_importador_persona' => $certificado->beneficiario->id,
                'form_id_certificado' => $certificado->id,
                'bandeja' => request('bandeja', 'recibidas'),
                'return_to' => $urlRetornoProductoTramite,
            ])
            : null;
    @endphp
    @include('certificados.partials.show_estilos')

    <div class="tramite-detail-v2" data-tramite-detail-active>
        <div class="tramite-shell">
            <header class="tramite-header">
                <h1 class="tramite-title">Detalle y seguimiento del trámite</h1>
            </header>

            {{-- Resumen superior: replica la franja principal del boceto. --}}
            <section class="tramite-summary-bar" aria-label="Resumen del trámite">
                <article class="tramite-summary-item">
                    <i class="fa-regular fa-file-lines tramite-summary-icon"></i>
                    <div>
                        <span class="tramite-summary-label">Código del trámite</span>
                        <span class="tramite-summary-value">{{ $certificado->codigo }}</span>
                    </div>
                </article>

                <article class="tramite-summary-item">
                    <i class="fa-solid fa-tag tramite-summary-icon"></i>
                    <div>
                        <span class="tramite-summary-label">Tipo de trámite</span>
                        <span class="tramite-summary-value">{{ $certificado->tipoCertificado?->nombre ?? 'Sin tipo' }}</span>
                    </div>
                </article>

                <article class="tramite-summary-item">
                    <i class="fa-regular fa-building tramite-summary-icon"></i>
                    <div>
                        <span class="tramite-summary-label">Beneficiario</span>
                        <span class="tramite-summary-value">{{ $nombrePersona($certificado->beneficiario) }}</span>
                    </div>
                </article>

                <article class="tramite-summary-item">
                    <i class="fa-regular fa-user tramite-summary-icon"></i>
                    <div>
                        <span class="tramite-summary-label">Tramitador</span>
                        <span class="tramite-summary-value">{{ $nombrePersona($certificado->tramitador) }}</span>
                    </div>
                </article>

                <article class="tramite-summary-item">
                    <i class="fa-regular fa-flag tramite-summary-icon"></i>
                    <div>
                        <span class="tramite-summary-label">Estado actual</span>
                        <span class="tramite-pill tramite-status-chip {{ $certificado->estado === 'OBSERVADO' ? 'tramite-pill-danger' : ($certificado->estado === 'EN_REVISION' ? 'tramite-pill-warn' : 'tramite-pill-ok') }}">
                            {{ $estadoTextoShow }}
                        </span>
                    </div>
                </article>
            </section>

            {{-- SECCION 4: revision de requisitos. Aqui el tecnico marca SI/NO y registra observaciones. --}}
            <section class="tramite-grid-main tramite-section-review">
                <div class="tramite-card">
                    <div class="tramite-card-head">
                        <h2 class="tramite-card-title">Revisión de requisitos</h2>
                    </div>

                    <div class="tramite-card-body">
                        @if ($puedeRevisarRequisitos)
                            <form action="{{ route('seguimientos_revision_tecnica', $seguimientoTecnicoActual) }}" method="POST">
                                @csrf

                                <div class="cert-show-table-wrap tramite-table-wrap">
                                    <table class="cert-show-table cert-review-table tramite-table tramite-requirements-table">
                                        <thead>
                                            <tr>
                                                <th class="cert-review-col-number">#</th>
                                                <th class="cert-review-col-requirement">Requisito</th>
                                                <th class="cert-review-col-evidence-code">Código evidencia</th>
                                                <th class="cert-review-col-evidence-description">Descripción evidencia</th>
                                                <th class="cert-review-col-result">Cumple</th>
                                                <th class="cert-review-col-status">Estado</th>
                                                <th class="cert-review-col-document">Evidencia</th>
                                                <th class="cert-review-col-observation">Observación</th>
                                                <th class="cert-review-col-history">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($certificado->certificadoRequisitos as $requisitoCertificado)
                                                @php
                                                    $documentoRequisito = $urlDocumentoRequisito($requisitoCertificado);
                                                    $codigoEvidencia = $codigoEvidenciaRequisito($requisitoCertificado);
                                                    $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                                    $descripcionEvidencia = $descripcionEvidenciaRequisito($requisitoCertificado);
                                                    $esFilaPago = $requisitoTieneEvidencia($requisitoCertificado, 'PAGO');
                                                    $comprobantePagoPrincipal = $urlArchivo($pagoPrincipalTramite?->comprobante);
                                                    $ultimaObservacion = $ultimaObservacionDeRequisito($requisitoCertificado);
                                                    $decisionActual = old(
                                                        "requisitos_revision.$loop->index.cumple",
                                                        $requisitoCertificado->cumple === 'SI'
                                                            ? 'SI'
                                                            : ($requisitoCertificado->cumple === 'NO' ? 'NO' : '')
                                                    );
                                                    $observacionActual = old(
                                                        "requisitos_revision.$loop->index.observacion",
                                                        $ultimaObservacion?->observacion
                                                    );
                                                    $observacionPropia = $ultimaObservacion
                                                        && (int) ($ultimaObservacion->revisionRequisito?->id_usuario_revisor) === (int) auth()->id();
                                                    $observacionDeOtro = $ultimaObservacion && !$observacionPropia;
                                                    $filaObservada = $decisionActual === 'NO' || $requisitoCertificado->estado === 'OBSERVADO';
                                                    $claseEstadoActual = $claseEstadoRequisito($requisitoCertificado->estado);
                                                @endphp
                                                <tr class="cert-requirement-row {{ $filaObservada ? 'is-observed tramite-row-observed' : '' }}"
                                                    data-requirement-title="{{ $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado' }}">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td data-requirement-title-cell>
                                                        {{ $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado' }}
                                                        <input type="hidden" name="requisitos_revision[{{ $loop->index }}][id]" value="{{ $requisitoCertificado->id }}">
                                                        <input type="hidden" name="requisitos_revision[{{ $loop->index }}][tocado]" value="{{ old("requisitos_revision.$loop->index.tocado", '0') }}" data-review-touched>
                                                        <input type="hidden" name="requisitos_revision[{{ $loop->index }}][cumple]" value="{{ $decisionActual }}" data-review-decision>
                                                    </td>
                                                    <td>
                                                        <span class="tramite-pill tramite-pill-neutral cert-evidence-code-chip">
                                                            {{ $codigoEvidencia }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="cert-evidence-description-text">{{ $descripcionEvidencia }}</span>
                                                    </td>
                                                    <td>
                                                        {{-- Casillas visibles para que el revisor confirme si el requisito cumple o no cumple. --}}
                                                        <div class="tramite-check-options" data-review-check-row>
                                                            <label class="tramite-check-option is-yes {{ $decisionActual === 'SI' ? 'is-selected' : '' }}">
                                                                <input type="checkbox" value="SI" data-review-check-option @checked($decisionActual === 'SI')>
                                                                <span class="tramite-check-box">
                                                                    <i class="fa-solid fa-check"></i>
                                                                </span>
                                                                SI
                                                            </label>

                                                            <label class="tramite-check-option is-no {{ $decisionActual === 'NO' ? 'is-selected' : '' }}">
                                                                <input type="checkbox" value="NO" data-review-check-option @checked($decisionActual === 'NO')>
                                                                <span class="tramite-check-box">
                                                                    <i class="fa-solid fa-xmark"></i>
                                                                </span>
                                                                NO
                                                            </label>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        {{-- Chip informativo: muestra el estado actual sin comportarse como boton. --}}
                                                        <span class="tramite-pill tramite-status-chip {{ $claseEstadoActual }}"
                                                            data-review-status-display>
                                                            @if (in_array($requisitoCertificado->estado, ['APROBADO', 'CUMPLE', 'ACTIVO'], true))
                                                                <i class="fa-solid fa-check" data-status-icon></i>
                                                            @elseif (in_array($requisitoCertificado->estado, ['OBSERVADO', 'REVISION_OBSERVADA'], true))
                                                                <i class="fa-solid fa-circle-exclamation" data-status-icon></i>
                                                            @else
                                                                <i class="fa-regular fa-clock" data-status-icon></i>
                                                            @endif
                                                            <span data-status-text>{{ $textoEstadoRequisito($requisitoCertificado->estado) }}</span>
                                                        </span>

                                                    </td>
                                                    <td>
                                                        @if ($esFilaPago && $tienePagoRegistrado)
                                                            @if ($comprobantePagoPrincipal)
                                                                <a href="{{ $comprobantePagoPrincipal }}" target="_blank" class="cert-show-pill cert-show-pill-ok tramite-doc-link">
                                                                    <i class="{{ $iconoEvidencia }}"></i>
                                                                    Ver comprobante
                                                                </a>
                                                            @else
                                                                <span class="cert-show-pill cert-show-pill-warn tramite-pill tramite-pill-warn">Sin comprobante</span>
                                                            @endif
                                                        @elseif ($esFilaPago && $puedeRegistrarPago)
                                                            <button type="button" class="cert-show-pill cert-show-pill-warn tramite-payment-inline-btn" data-open-payment-modal>
                                                                <i class="{{ $iconoEvidencia }}"></i>
                                                                Registrar pago
                                                            </button>
                                                        @elseif ($documentoRequisito)
                                                            <a href="{{ $documentoRequisito }}" target="_blank"
                                                                class="cert-show-pill cert-show-pill-ok tramite-doc-link"
                                                                data-document-link
                                                                data-document-default="{{ $textoEvidenciaRequisito($codigoEvidencia, false) }}"
                                                                data-document-observed="{{ $textoEvidenciaRequisito($codigoEvidencia, true) }}">
                                                                <i class="{{ $iconoEvidencia }}"></i>
                                                                <span data-document-text>{{ $textoEvidenciaRequisito($codigoEvidencia, $filaObservada) }}</span>
                                                            </a>
                                                        @else
                                                            <span class="cert-show-pill cert-show-pill-warn tramite-pill tramite-pill-warn">Sin evidencia</span>
                                                        @endif
                                                    </td>
                                                    <td>
                                                        <input type="hidden" name="requisitos_revision[{{ $loop->index }}][observacion]" value="{{ $observacionActual }}" data-observation-input>

                                                        {{-- Observacion visual como el recuadro simple del boceto. --}}
                                                        <span class="tramite-observation-box {{ $filaObservada && filled($observacionActual) ? 'is-danger' : '' }}"
                                                            data-observation-display>
                                                            {{ filled($observacionActual) ? $observacionActual : 'Sin observación' }}
                                                        </span>

                                                        <div class="cert-review-observation-box {{ $decisionActual === 'NO' && filled($observacionActual) ? 'is-visible' : '' }}" data-observation-box>
                                                            <span class="cert-review-observation-text" data-observation-text>{{ $observacionActual }}</span>
                                                            <button type="button" class="cert-review-edit" data-edit-observation data-observation-owner="{{ $observacionPropia ? '1' : '0' }}">
                                                                <i class="fa-solid fa-pen"></i>
                                                                {{ $observacionDeOtro ? 'Nueva observación' : 'Editar' }}
                                                            </button>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="cert-history-button" data-requirement-history-button data-requirement-id="{{ $requisitoCertificado->id }}">
                                                            <i class="fa-regular fa-clock"></i>
                                                            Historial
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center">Este trámite no tiene requisitos registrados.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="tramite-actions-row">
                                    @if ($certificado->estado === 'OBSERVADO')
                                        <button type="button" class="tramite-btn tramite-btn-muted" disabled>
                                            <i class="fa-solid fa-arrow-right"></i>
                                            Derivar
                                        </button>
                                        <button type="button" class="tramite-btn tramite-btn-muted" disabled>
                                            <i class="fa-regular fa-floppy-disk"></i>
                                            Guardar revisión
                                        </button>
                                        <span class="tramite-warning-box">
                                            <i class="fa-solid fa-triangle-exclamation"></i>
                                            Trámite observado: no se puede derivar ni continuar revisión hasta que el solicitante corrija.
                                        </span>
                                    @else
                                        <button type="submit" class="tramite-btn tramite-btn-primary">
                                            <i class="fa-regular fa-floppy-disk"></i>
                                            Guardar revisión
                                        </button>

                                        @if ($puedeFinalizarTramite)
                                            <button type="submit" form="form-finalizar-tramite" class="tramite-btn tramite-btn-ok">
                                                <i class="fa-solid fa-circle-check"></i>
                                                Finalizar trámite
                                            </button>
                                        @endif

                                        @if ($puedeEmitirCertificado)
                                            <a href="{{ route('certificados_emitir', $certificado) }}" class="tramite-btn tramite-btn-emit">
                                                <i class="fa-regular fa-file-lines"></i>
                                                Emitir certificado
                                            </a>
                                        @endif

                                        @if ($puedeNotificarCorreccion)
                                            <button type="submit" form="form-notificar-correccion-v2" class="tramite-btn tramite-btn-notify" onclick="return confirm('Se devolverá el trámite al solicitante para que corrija los requisitos observados. ¿Desea continuar?')">
                                                <i class="fa-solid fa-paper-plane"></i>
                                                Notificar solicitante
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </form>
                            @if ($puedeNotificarCorreccion)
                                <form id="form-notificar-correccion-v2" action="{{ route('seguimientos_notificar_correccion', $seguimientoTecnicoActual) }}" method="POST">
                                    @csrf
                                </form>
                            @endif
                            @if ($puedeFinalizarTramite)
                                <form id="form-finalizar-tramite" action="{{ route('seguimientos_finalizar_tramite', $seguimientoTecnicoActual) }}" method="POST">
                                    @csrf
                                </form>
                            @endif
                        @elseif ($seguimientoCorreccionActual)
                            {{-- Vista del solicitante: solo consulta las observaciones. La recepcion de correccion la registra un funcionario. --}}
                            <div id="form-correccion-requisitos-v2">

                                <div class="cert-show-table-wrap tramite-table-wrap">
                                    <table class="cert-show-table cert-review-table tramite-table tramite-requirements-table cert-correction-table">
                                        <thead>
                                            <tr>
                                                <th class="cert-review-col-number">#</th>
                                                <th>Requisito</th>
                                                <th class="cert-review-col-result">Cumple</th>
                                                <th>Estado</th>
                                                <th>Documento</th>
                                                <th>Observación del técnico</th>
                                                <th class="cert-review-col-history">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                                    @forelse ($certificado->certificadoRequisitos->where('cumple', 'NO') as $requisitoCertificado)
                                                        @php
                                                            $documentoRequisito = $urlDocumentoRequisito($requisitoCertificado);
                                                            $codigoEvidencia = $codigoEvidenciaRequisito($requisitoCertificado);
                                                            $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                                            // Ultima observacion que el tecnico notifico para este requisito observado.
                                                            $ultimaObservacionCorreccion = $ultimaObservacionDeRequisito($requisitoCertificado);
                                                        @endphp
                                                <tr class="cert-requirement-row is-observed tramite-row-observed">
                                                    <td>{{ $loop->iteration }}</td>
                                                    <td>
                                                        <strong class="block text-slate-800">
                                                            {{ $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado' }}
                                                        </strong>
                                                    </td>
                                                    <td>
                                                        <span class="tramite-pill tramite-status-chip tramite-pill-danger">
                                                            <i class="fa-solid fa-xmark"></i>
                                                            No
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <span class="tramite-pill tramite-pill-danger">
                                                            <i class="fa-solid fa-circle-exclamation"></i>
                                                            Observado
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <div class="cert-correction-document-cell">
                                                            @if ($documentoRequisito)
                                                                <a href="{{ $documentoRequisito }}" target="_blank" class="cert-show-pill cert-show-pill-danger tramite-doc-link">
                                                                    <i class="{{ $iconoEvidencia }}"></i>
                                                                    {{ $textoEvidenciaRequisito($codigoEvidencia, true) }}
                                                                </a>
                                                            @else
                                                                <span class="cert-show-pill cert-show-pill-warn tramite-pill tramite-pill-warn">Sin evidencia</span>
                                                            @endif

                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="cert-correction-observation-text">
                                                            {{ $ultimaObservacionCorreccion?->observacion ?? 'Sin observación' }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <button type="button" class="cert-history-button" data-requirement-history-button data-requirement-id="{{ $requisitoCertificado->id }}">
                                                            <i class="fa-regular fa-clock"></i>
                                                            Historial
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="7" class="text-center">Sin requisitos pendientes.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                            </div>
                        @else
                            <div class="cert-show-table-wrap tramite-table-wrap">
                                <table class="cert-show-table cert-review-table tramite-table tramite-requirements-table">
                                    <thead>
                                        <tr>
                                            <th class="cert-review-col-number">#</th>
                                            <th>Requisito</th>
                                            <th class="cert-review-col-result">Cumple</th>
                                            <th>Estado</th>
                                            <th>Documento</th>
                                            <th class="cert-review-col-observation">Observación</th>
                                            <th class="cert-review-col-history">Acción</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                            @forelse ($certificado->certificadoRequisitos as $requisitoCertificado)
                                                @php
                                                    $documentoRequisito = $urlDocumentoRequisito($requisitoCertificado);
                                                    $codigoEvidencia = $codigoEvidenciaRequisito($requisitoCertificado);
                                                    $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                                    $esFilaPago = $requisitoTieneEvidencia($requisitoCertificado, 'PAGO');
                                                    $comprobantePagoPrincipal = $urlArchivo($pagoPrincipalTramite?->comprobante);
                                                $ultimaObservacion = $ultimaObservacionDeRequisito($requisitoCertificado);
                                                $observacionInterna = $esSolicitante && $requisitoCertificado->estado === 'REVISION_OBSERVADA';
                                                $textoCumple = $observacionInterna || $requisitoCertificado->estado === 'PENDIENTE_REVISION'
                                                    ? 'Pendiente'
                                                    : ($requisitoCertificado->cumple === 'SI' ? 'Cumple' : 'Observado');
                                                $claseCumple = match ($textoCumple) {
                                                    'Cumple' => 'tramite-pill-ok',
                                                    'Observado' => 'tramite-pill-danger',
                                                    default => 'tramite-pill-warn',
                                                };
                                                $textoCumpleCorto = match ($textoCumple) {
                                                    'Cumple' => 'Sí',
                                                    'Observado' => 'No',
                                                    default => 'Pendiente',
                                                };
                                                $textoObservacionRequisito = $requisitoCertificado->estado === 'PENDIENTE_REVISION'
                                                    ? 'Pendiente de revisión'
                                                    : ($observacionInterna ? 'Pendiente de notificación' : ($ultimaObservacion?->observacion ?? 'Sin observación'));
                                            @endphp
                                            <tr class="cert-requirement-row {{ $textoCumple === 'Observado' || $requisitoCertificado->estado === 'OBSERVADO' ? 'is-observed tramite-row-observed' : '' }}">
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado' }}</td>
                                                <td>
                                                    <span class="tramite-pill tramite-status-chip {{ $claseCumple }}">
                                                        @if ($textoCumple === 'Cumple')
                                                            <i class="fa-solid fa-check"></i>
                                                        @elseif ($textoCumple === 'Observado')
                                                            <i class="fa-solid fa-circle-exclamation"></i>
                                                        @else
                                                            <i class="fa-regular fa-clock"></i>
                                                        @endif
                                                        {{ $textoCumpleCorto }}
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="tramite-pill tramite-status-chip {{ $claseCumple }}">
                                                        {{ $textoEstadoRequisito($requisitoCertificado->estado) }}
                                                    </span>
                                                </td>
                                                <td>
                                                    @if ($esFilaPago && $tienePagoRegistrado)
                                                        @if ($comprobantePagoPrincipal)
                                                            <a href="{{ $comprobantePagoPrincipal }}" target="_blank" class="cert-show-pill cert-show-pill-ok tramite-doc-link">
                                                                <i class="{{ $iconoEvidencia }}"></i>
                                                                Ver comprobante
                                                            </a>
                                                        @else
                                                            <span class="cert-show-pill cert-show-pill-warn tramite-pill tramite-pill-warn">Sin comprobante</span>
                                                        @endif
                                                    @elseif ($esFilaPago && $puedeRegistrarPago)
                                                        <button type="button" class="cert-show-pill cert-show-pill-warn tramite-payment-inline-btn" data-open-payment-modal>
                                                            <i class="{{ $iconoEvidencia }}"></i>
                                                            Registrar pago
                                                        </button>
                                                    @elseif ($documentoRequisito)
                                                        <a href="{{ $documentoRequisito }}" target="_blank" class="cert-show-pill cert-show-pill-ok tramite-doc-link">
                                                            <i class="{{ $iconoEvidencia }}"></i>
                                                            {{ $textoEvidenciaRequisito($codigoEvidencia, $textoCumple === 'Observado') }}
                                                        </a>
                                                    @else
                                                        <span class="cert-show-pill cert-show-pill-warn tramite-pill tramite-pill-warn">Sin evidencia</span>
                                                    @endif
                                                </td>
                                                <td>{{ $textoObservacionRequisito }}</td>
                                                <td>
                                                    <button type="button" class="cert-history-button" data-requirement-history-button data-requirement-id="{{ $requisitoCertificado->id }}">
                                                        <i class="fa-regular fa-clock"></i>
                                                        Historial
                                                    </button>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">Este trámite no tiene requisitos registrados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>

                            @if ($certificado->estado === 'OBSERVADO' && !$esSolicitante)
                                @if ($puedeRegistrarCorreccionRecibida)
                                    {{-- Corrección presencial: no exige PDF, solo registra que INSO recibió la corrección. --}}
                                    <form action="{{ route('seguimientos_registrar_correccion_recibida', $seguimientoAtencionActual) }}" method="POST" class="tramite-actions-row" data-confirm-received-correction>
                                        @csrf
                                        <button type="submit" class="tramite-btn tramite-btn-primary">
                                            <i class="fa-solid fa-clipboard-check"></i>
                                            Registrar corrección recibida
                                        </button>
                                    </form>
                                @else
                                <div class="tramite-actions-row">
                                    <button type="button" class="tramite-btn tramite-btn-muted" disabled>
                                        <i class="fa-solid fa-arrow-right"></i>
                                        Derivar
                                    </button>
                                    <button type="button" class="tramite-btn tramite-btn-muted" disabled>
                                        <i class="fa-regular fa-floppy-disk"></i>
                                        Guardar revisión
                                    </button>
                                    <span class="tramite-warning-box">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        Trámite observado: no se puede derivar ni continuar revisión hasta que el solicitante corrija.
                                    </span>
                                </div>
                                @endif
                            @endif
                        @endif
                    </div>
                </div>

                {{-- SECCION 5: historial del requisito seleccionado. Se alimenta con observaciones y decisiones tecnicas. --}}
                <aside class="tramite-card tramite-history-panel">
                    <div class="tramite-card-head">
                        <h2 class="tramite-card-title">Historial del requisito seleccionado</h2>
                    </div>
                    <div class="tramite-card-body">
                        <p class="cert-requirement-history-subtitle" data-requirement-history-title>
                            Seleccione un requisito para ver observaciones, correcciones y decisiones.
                        </p>
                        <div class="tramite-history-list" data-requirement-history-list>
                            <div class="cert-history-empty">El historial aparece aquí al seleccionar un requisito.</div>
                        </div>
                    </div>
                </aside>
            </section>

            @if ($requierePagoTramite && $puedeRegistrarPago)
                {{-- Registro de pago: se abre en modal para no cargar el detalle del tramite. --}}
                <div id="modalRegistrarPagoTramite"
                    class="tramite-modal {{ $abrirModalPago ? 'is-open' : '' }}"
                    data-payment-modal
                    @if ($abrirModalPago) data-open-on-error="1" @endif
                    aria-hidden="{{ $abrirModalPago ? 'false' : 'true' }}">
                    <div class="tramite-modal-backdrop" data-close-payment-modal></div>
                    <section class="tramite-modal-panel" role="dialog" aria-modal="true" aria-labelledby="tituloModalPagoTramite">
                        <div class="tramite-modal-head">
                            <div>
                                <h2 id="tituloModalPagoTramite" class="tramite-card-title">
                                    <i class="fa-solid fa-credit-card"></i>
                                    Registrar pago
                                </h2>
                                <p>Complete el pago relacionado a este trámite.</p>
                            </div>
                            <button type="button" class="tramite-modal-close" data-close-payment-modal aria-label="Cerrar modal de pago">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <form action="{{ route('pagos_store') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="form_id_certificado" value="{{ $certificado->id }}">
                            <input type="hidden" name="form_bandeja" value="{{ request('bandeja', 'recibidas') }}">

                            <div class="tramite-payment-form-grid">
                                <div class="tramite-payment-field-4">
                                    <label class="cert-show-label" for="form_id_procedencia_pago">Procedencia</label>
                                    <select id="form_id_procedencia_pago" name="form_id_procedencia_pago" class="cert-review-select @error('form_id_procedencia_pago') is-invalid @enderror" required>
                                        <option value="">Seleccione procedencia</option>
                                        @foreach ($procedenciasPago as $procedencia)
                                            <option value="{{ $procedencia->id }}" @selected(old('form_id_procedencia_pago') == $procedencia->id)>
                                                {{ $procedencia->codigo }}{{ $procedencia->descripcion ? ' - ' . $procedencia->descripcion : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form_id_procedencia_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-4">
                                    <label class="cert-show-label" for="form_tipo_pago">Tipo de pago</label>
                                    <select id="form_tipo_pago" name="form_tipo_pago" class="cert-review-select @error('form_tipo_pago') is-invalid @enderror" required>
                                        <option value="">Seleccione tipo</option>
                                        @foreach (\App\Models\Pago::TIPOS_PAGOS as $valor => $texto)
                                            <option value="{{ $valor }}" @selected(old('form_tipo_pago') === $valor)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                    @error('form_tipo_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-4">
                                    <label class="cert-show-label" for="form_fecha_pago">Fecha de pago</label>
                                    <input id="form_fecha_pago" name="form_fecha_pago" type="date" class="cert-review-select @error('form_fecha_pago') is-invalid @enderror" value="{{ old('form_fecha_pago', now()->toDateString()) }}" required>
                                    @error('form_fecha_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-3">
                                    <label class="cert-show-label" for="form_monto_pago">Monto</label>
                                    <input id="form_monto_pago" name="form_monto_pago" type="number" min="0.01" step="0.01" class="cert-review-select @error('form_monto_pago') is-invalid @enderror" value="{{ old('form_monto_pago') }}" placeholder="0.00" required>
                                    @error('form_monto_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-6">
                                    <label class="cert-show-label" for="form_comprobante_pago">Comprobante PDF</label>
                                    <div class="tramite-payment-pdf @error('form_comprobante_pago') is-invalid @enderror">
                                        <input id="form_comprobante_pago" name="form_comprobante_pago" type="file" accept="application/pdf,.pdf" class="hidden" data-payment-pdf-input>
                                        <span class="tramite-payment-pdf-icon"><i class="fa-regular fa-file-pdf"></i></span>
                                        <span class="tramite-payment-pdf-name" data-payment-pdf-name>Sin PDF seleccionado</span>
                                        <span class="tramite-payment-pdf-actions">
                                            <button type="button" class="tramite-payment-pdf-button is-select" data-payment-pdf-select>
                                                <i class="fa-solid fa-upload"></i>
                                                Seleccionar
                                            </button>
                                            <button type="button" class="tramite-payment-pdf-button is-view" data-payment-pdf-view disabled>
                                                <i class="fa-regular fa-eye"></i>
                                                Ver
                                            </button>
                                            <button type="button" class="tramite-payment-pdf-button is-remove" data-payment-pdf-remove disabled>
                                                <i class="fa-solid fa-trash-can"></i>
                                                Quitar
                                            </button>
                                        </span>
                                    </div>
                                    @error('form_comprobante_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="tramite-actions-row">
                                <button type="button" class="tramite-btn tramite-btn-secondary" data-close-payment-modal>
                                    Cancelar
                                </button>
                                <button type="submit" class="tramite-btn tramite-btn-primary">
                                    <i class="fa-solid fa-floppy-disk"></i>
                                    Registrar pago
                                </button>
                            </div>
                        </form>
                    </section>
                </div>
            @endif

            @if ($certificado->estado !== 'OBSERVADO' && ($puedeAsignarTecnico || ($puedeRevisarRequisitos && $seguimientoTecnicoActual?->id_usuario_siguiente)))
                {{-- SECCION 6: asignacion o derivacion tecnica. Solo se elige funcionario y se agrega una descripcion. --}}
                <section class="tramite-card mb-4 tramite-section-technical">
                    <div class="tramite-card-head">
                        <h2 class="tramite-card-title">Asignación técnica</h2>
                    </div>
                    <div class="tramite-card-body">
                        @if ($puedeAsignarTecnico)
                            <form action="{{ route('seguimientos_asignar_tecnico', $seguimientoAtencionActual) }}" method="POST" data-prevent-double-submit data-loading-button="Asignando...">
                                @csrf
                                <div class="cert-derive-grid">
                                    @include('certificados.partials.selector_tecnico', [
                                        'selectId' => 'id_tecnico_v2',
                                        'selectName' => 'id_tecnico',
                                        'selectLabel' => 'Tecnico asignado',
                                        'placeholder' => 'Seleccione funcionario',
                                        'oldValue' => old('id_tecnico'),
                                        'tecnicos' => $tecnicosDerivacion,
                                        'excluirIds' => [(int) $seguimientoAtencionActual?->id_usuario_siguiente],
                                    ])
                                    <div>
                                        <label class="cert-show-label" for="descripcion_derivacion_v2">Descripción de derivación</label>
                                        <textarea id="descripcion_derivacion_v2" class="cert-review-textarea" name="descripcion_derivacion" placeholder="Ingrese una descripción opcional">{{ old('descripcion_derivacion') }}</textarea>
                                    </div>
                                    <button type="submit" class="tramite-btn tramite-btn-primary">
                                        <i class="fa-solid fa-user-check"></i>
                                        Asignar
                                    </button>
                                </div>
                            </form>
                        @endif

                        @if ($puedeRevisarRequisitos && !$esJefeUnidad && $seguimientoTecnicoActual?->id_usuario_siguiente)
                            <form action="{{ route('seguimientos_derivar_tecnico', $seguimientoTecnicoActual) }}" method="POST" class="mt-4" data-prevent-double-submit data-loading-button="Derivando...">
                                @csrf
                                <div class="cert-derive-grid is-transfer">
                                    @include('certificados.partials.selector_tecnico', [
                                        'selectId' => 'id_tecnico_destino_v2',
                                        'selectName' => 'id_tecnico_destino',
                                        'selectLabel' => 'Funcionario destino',
                                        'placeholder' => 'Seleccione funcionario',
                                        'oldValue' => old('id_tecnico_destino'),
                                        'tecnicos' => $tecnicosDerivacion,
                                        'excluirIds' => [
                                            (int) $seguimientoAtencionActual?->id_usuario_siguiente,
                                            (int) $seguimientoTecnicoActual->id_usuario_siguiente,
                                        ],
                                    ])
                                    <div>
                                        <label class="cert-show-label" for="motivo_derivacion_v2">Motivo de derivación</label>
                                        <textarea id="motivo_derivacion_v2" class="cert-review-textarea" name="motivo_derivacion" placeholder="Explique por qué deriva este trámite." required>{{ old('motivo_derivacion') }}</textarea>
                                    </div>
                                    <button type="submit" class="tramite-btn tramite-btn-primary">
                                        <i class="fa-solid fa-share"></i>
                                        Derivar
                                    </button>
                                </div>
                            </form>
                        @endif
                    </div>
                </section>
            @endif

            {{-- SECCION 1: datos base del tramite. Se separa solicitante, tramitador e inicio para no mezclar responsabilidades. --}}
            <section class="tramite-card tramite-section-detail">
                <div class="tramite-card-head">
                    <h2 class="tramite-card-title">Informacion principal</h2>
                </div>
                <div class="tramite-card-body">
                    <div class="tramite-detail-grid">
                        <article class="tramite-detail-panel tramite-panel-beneficiario">
                            <h3 class="tramite-detail-title">
                                <i class="fa-regular fa-file-lines"></i>
                                Solicitante / Beneficiario
                            </h3>
                            <dl class="tramite-definition is-compact">
                                <div class="tramite-definition-row">
                                    <dt>Solicitante</dt>
                                    <dd>{{ $nombrePersona($certificado->beneficiario) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>CI / NIT</dt>
                                    <dd>{{ $identificacionPersona($certificado->beneficiario) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Correo electronico</dt>
                                    <dd>{{ $certificado->beneficiario?->correo ?? 'Sin correo' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Telefono</dt>
                                    <dd>{{ $telefonoPersona($certificado->beneficiario) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Territorio</dt>
                                    <dd>{{ $certificado->beneficiario?->territorio?->nombre ?? 'Sin territorio' }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article class="tramite-detail-panel tramite-panel-tramitador">
                            <h3 class="tramite-detail-title">
                                <i class="fa-regular fa-user"></i>
                                Tramitador
                            </h3>
                            <dl class="tramite-definition is-compact">
                                <div class="tramite-definition-row">
                                    <dt>Tramitador</dt>
                                    <dd>{{ $nombrePersona($certificado->tramitador) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>CI / NIT</dt>
                                    <dd>{{ $identificacionPersona($certificado->tramitador) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Correo electronico</dt>
                                    <dd>{{ $certificado->tramitador?->correo ?? 'Sin correo' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Telefono</dt>
                                    <dd>{{ $telefonoPersona($certificado->tramitador) }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Territorio</dt>
                                    <dd>{{ $certificado->tramitador?->territorio?->nombre ?? 'Sin territorio' }}</dd>
                                </div>
                            </dl>
                        </article>

                        <article class="tramite-detail-panel tramite-panel-inicio">
                            <h3 class="tramite-detail-title">
                                <i class="fa-solid fa-users"></i>
                                Inicio del tramite
                            </h3>
                            <dl class="tramite-definition is-compact">
                                <div class="tramite-definition-row">
                                    <dt>Fecha de solicitud</dt>
                                    <dd>{{ $certificado->fecha_inicio?->format('d/m/Y') ?? 'Sin fecha' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Codigo de solicitud</dt>
                                    <dd>{{ $certificado->codigo ?: 'Auto-generado al enviar' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Area que recibe</dt>
                                    <dd>{{ $seguimientoActualDetalle?->usuarioSiguiente?->funcionario?->cargos?->pluck('area.nombre')->filter()->implode(', ') ?: 'Sin area asignada' }}</dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Registrado por</dt>
                                    <dd class="tramite-user-stack">
                                        <span class="tramite-user-name">{{ $nombreUsuario($seguimientoOrigenDetalle?->usuarioOrigen, 'Sin usuario de registro') }}</span>
                                        @if ($seguimientoOrigenDetalle?->usuarioOrigen)
                                            <span class="tramite-user-cargo">
                                                {{ $cargoUsuario($seguimientoOrigenDetalle->usuarioOrigen) }}
                                            </span>
                                        @endif
                                    </dd>
                                </div>
                                <div class="tramite-definition-row">
                                    <dt>Primer destino</dt>
                                    <dd class="tramite-user-stack">
                                        <span class="tramite-user-name">{{ $nombreUsuario($seguimientoOrigenDetalle?->usuarioSiguiente, 'Sin destino inicial') }}</span>
                                        <span class="tramite-user-cargo">
                                            {{ $cargoUsuario($seguimientoOrigenDetalle?->usuarioSiguiente) }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </article>

                        @if ($requiereProductoTramite)
                        <article class="tramite-detail-panel tramite-panel-productos is-wide">
                            {{-- SECCION 2: productos asociados al tramite. Cada producto se despliega para no repetir informacion en pantalla. --}}
                            <div class="tramite-section-title-row">
                                <h3 class="tramite-detail-title">
                                    <i class="fa-solid fa-boxes-stacked"></i>
                                    Productos asociados
                                </h3>

                                @if ($urlRegistrarProductoTramite)
                                    <a href="{{ $urlRegistrarProductoTramite }}" class="tramite-product-register-btn">
                                        <i class="fa-solid fa-plus"></i>
                                        Registrar producto
                                    </a>
                                @endif
                            </div>
                            <div class="tramite-product-list">
                                @forelse ($registrosPorProducto as $grupoRegistros)
                                    @php
                                        $primerRegistro = $grupoRegistros->first();
                                        $producto = $primerRegistro?->producto;
                                        $presentacionesProducto = $producto?->presentaciones ?? collect();
                                        $ingredientesProducto = $producto?->ingredientesProductos ?? collect();
                                    @endphp
                                    <details class="tramite-product is-color-{{ (($loop->index % 4) + 1) }}" @if ($loop->first) open @endif>
                                        <summary class="tramite-product-head">
                                            <div>
                                                <h4 class="tramite-product-title">{{ $producto?->nombre_comercial ?? 'Producto sin nombre comercial' }}</h4>
                                                <div class="tramite-product-meta">
                                                    {{ $producto?->codigo ?: 'Sin código' }} · {{ $grupoRegistros->count() }} registro{{ $grupoRegistros->count() === 1 ? '' : 's' }}
                                                </div>
                                            </div>
                                            <span class="tramite-product-status">
                                                <i class="fa-solid fa-box"></i>
                                                {{ $grupoRegistros->count() }} registro{{ $grupoRegistros->count() === 1 ? '' : 's' }}
                                            </span>
                                        </summary>
                                        <div class="tramite-product-body">
                                            <section class="tramite-product-section">
                                                <h4 class="tramite-product-section-title">
                                                    <i class="fa-solid fa-circle-info"></i>
                                                    Datos del producto
                                                </h4>
                                                <dl class="tramite-definition is-compact">
                                                    <div class="tramite-definition-row">
                                                        <dt>Tipo</dt>
                                                        <dd>{{ $producto?->tipoProducto?->descripcion ?? 'Sin tipo' }}</dd>
                                                    </div>
                                                    <div class="tramite-definition-row">
                                                        <dt>Nombre científico</dt>
                                                        <dd>{{ $producto?->nombre_cientifico ?: 'Sin dato' }}</dd>
                                                    </div>
                                                    <div class="tramite-definition-row">
                                                        <dt>Clasificación</dt>
                                                        <dd>{{ $producto?->clasificacionProducto?->nombre ?: 'Sin clasificación' }}</dd>
                                                    </div>
                                                    <div class="tramite-definition-row">
                                                        <dt>Fabricante</dt>
                                                        <dd>{{ $producto?->fabricante?->nombre ?? 'Sin fabricante' }}</dd>
                                                    </div>
                                                    <div class="tramite-definition-row">
                                                        <dt>País / territorio</dt>
                                                        <dd>{{ $producto?->territorio?->nombre ?? 'Sin país' }}</dd>
                                                    </div>
                                                    <div class="tramite-definition-row">
                                                        <dt>Importador</dt>
                                                        <dd>{{ $nombrePersona($producto?->importadorPersona) }}</dd>
                                                    </div>
                                                </dl>
                                            </section>

                                            <section class="tramite-product-section">
                                                <h4 class="tramite-product-section-title">
                                                    <i class="fa-solid fa-clipboard-list"></i>
                                                    Registros
                                                </h4>
                                                <div class="tramite-table-wrap">
                                                <table class="tramite-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Registro</th>
                                                            <th>Vigencia</th>
                                                            <th>Cantidad registro</th>
                                                            <th>Presentación</th>
                                                            <th>Etiqueta</th>
                                                            <th>Estado</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach ($grupoRegistros as $registro)
                                                            @php
                                                                $presentacionRegistro = $registro->presentacion;
                                                                $etiquetaRegistro = $urlArchivo($presentacionRegistro?->url_etiqueta);
                                                            @endphp
                                                            <tr>
                                                                <td>{{ $registro->codigo_autorizacion ?: 'Sin código' }}</td>
                                                                <td>{{ $registro->fecha_vigencia ? \Illuminate\Support\Carbon::parse($registro->fecha_vigencia)->format('d/m/Y') : 'Sin vigencia' }}</td>
                                                                <td>{{ trim(($registro->cantidad ?? '') . ' ' . ($registro->catalogoUnidad?->nombre ?? '')) ?: 'Sin cantidad' }}</td>
                                                                <td>
                                                                    {{ trim(($presentacionRegistro?->cantidad ?? '') . ' ' . ($presentacionRegistro?->catalogoUnidad?->nombre ?? '')) ?: 'Sin cantidad' }}
                                                                    @if ($presentacionRegistro?->descripcion)
                                                                        <div class="text-xs font-semibold text-slate-500">{{ $presentacionRegistro->descripcion }}</div>
                                                                    @endif
                                                                </td>
                                                                <td>
                                                                    @if ($etiquetaRegistro)
                                                                        <a href="{{ $etiquetaRegistro }}" target="_blank" class="tramite-doc-link">
                                                                            <i class="fa-regular fa-file-pdf"></i>
                                                                            Ver etiqueta
                                                                        </a>
                                                                    @else
                                                                        <span class="tramite-pill tramite-pill-warn">Sin etiqueta</span>
                                                                    @endif
                                                                </td>
                                                                <td>{{ $registro->estado ?? 'Sin estado' }}</td>
                                                            </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                                </div>
                                            </section>

                                            <section class="tramite-product-section">
                                                <h4 class="tramite-product-section-title">
                                                    <i class="fa-solid fa-flask"></i>
                                                    Ingredientes
                                                </h4>
                                                <div class="tramite-table-wrap">
                                                <table class="tramite-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Ingrediente</th>
                                                            <th>Composición</th>
                                                            <th>Riesgo de salud</th>
                                                            <th>Porcentaje</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($ingredientesProducto as $ingredienteProducto)
                                                            <tr>
                                                                <td>{{ $ingredienteProducto->ingrediente?->nombre ?? 'Sin ingrediente' }}</td>
                                                                <td>{{ $ingredienteProducto->ingrediente?->composicion ?? 'Sin composición' }}</td>
                                                                <td>{{ $ingredienteProducto->ingrediente?->riesgo_salud ?? 'Sin dato' }}</td>
                                                                <td>{{ $ingredienteProducto->porcentaje !== null ? $ingredienteProducto->porcentaje . '%' : 'Sin porcentaje' }}</td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="4" class="text-center">Este producto no tiene ingredientes registrados.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                                </div>
                                            </section>

                                            <section class="tramite-product-section">
                                                <h4 class="tramite-product-section-title">
                                                    <i class="fa-solid fa-box-open"></i>
                                                    Presentaciones
                                                </h4>
                                                <div class="tramite-table-wrap">
                                                <table class="tramite-table">
                                                    <thead>
                                                        <tr>
                                                            <th>Presentación</th>
                                                            <th>Descripción</th>
                                                            <th>Etiqueta</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @forelse ($presentacionesProducto as $presentacionProducto)
                                                            @php
                                                                $etiquetaPresentacion = $urlArchivo($presentacionProducto->url_etiqueta);
                                                            @endphp
                                                            <tr>
                                                                <td>{{ trim(($presentacionProducto->cantidad ?? '') . ' ' . ($presentacionProducto->catalogoUnidad?->nombre ?? '')) ?: 'Sin cantidad' }}</td>
                                                                <td>{{ $presentacionProducto->descripcion ?: 'Sin descripción' }}</td>
                                                                <td>
                                                                    @if ($etiquetaPresentacion)
                                                                        <a href="{{ $etiquetaPresentacion }}" target="_blank" class="tramite-doc-link">
                                                                            <i class="fa-regular fa-file-pdf"></i>
                                                                            Ver documento
                                                                        </a>
                                                                    @else
                                                                        <span class="tramite-pill tramite-pill-warn">Sin PDF</span>
                                                                    @endif
                                                                </td>
                                                            </tr>
                                                        @empty
                                                            <tr>
                                                                <td colspan="3" class="text-center">Este producto no tiene presentaciones registradas.</td>
                                                            </tr>
                                                        @endforelse
                                                    </tbody>
                                                </table>
                                                </div>
                                            </section>
                                        </div>
                                    </details>
                                @empty
                                    <div class="cert-history-empty">Este trámite no tiene productos asociados.</div>
                                @endforelse
                            </div>
                        </article>
                        @endif

                        @if ($requierePagoTramite)
                        <article class="tramite-detail-panel tramite-panel-pagos is-wide">
                            {{-- SECCION 3: pagos relacionados al tramite. Solo se muestra lo guardado en la base de datos. --}}
                            <div class="tramite-section-title-row">
                                <h3 class="tramite-detail-title">
                                    <i class="fa-solid fa-credit-card"></i>
                                    Pagos
                                </h3>

                                @if ($requierePagoTramite && $puedeRegistrarPago)
                                    <button type="button" class="tramite-product-register-btn tramite-payment-register-btn" data-open-payment-modal>
                                        <i class="fa-solid fa-plus"></i>
                                        Registrar pago
                                    </button>
                                @endif
                            </div>
                            <div class="tramite-table-wrap">
                                <table class="tramite-table">
                                    <thead>
                                        <tr>
                                            <th>Procedencia</th>
                                            <th>Tipo</th>
                                            <th>Fecha pago</th>
                                            <th>Monto</th>
                                            <th>Cliente</th>
                                            <th>Registrado por</th>
                                            <th>Fecha registro</th>
                                            <th>Comprobante</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($certificado->pagos as $pago)
                                            @php
                                                $comprobantePago = $urlArchivo($pago->comprobante);
                                            @endphp
                                            <tr>
                                                <td>{{ $pago->procedencia?->descripcion ?? 'Sin procedencia' }}</td>
                                                <td>{{ $textoTipoPago($pago->tipo_pago) }}</td>
                                                <td>{{ $fechaCorta($pago->fecha) }}</td>
                                                <td>{{ number_format((float) $pago->monto, 2, ',', '.') }} Bs.</td>
                                                <td>{{ $nombrePersona($pago->clientePersona) }}</td>
                                                <td>
                                                    <span class="block font-semibold text-slate-800">
                                                        {{ $nombreUsuario($pago->funcionarioUsuario, 'Sin funcionario') }}
                                                    </span>
                                                    <span class="block text-xs font-semibold text-slate-500">
                                                        {{ $cargoUsuario($pago->funcionarioUsuario) }}
                                                    </span>
                                                </td>
                                                <td>{{ $fechaCorta($pago->fecha_validacion) }}</td>
                                                <td>
                                                    @if ($comprobantePago)
                                                        <a href="{{ $comprobantePago }}" target="_blank" class="tramite-doc-link">
                                                            <i class="fa-regular fa-file-pdf"></i>
                                                            Ver comprobante
                                                        </a>
                                                    @else
                                                        <span class="tramite-pill tramite-pill-warn">Sin PDF</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="9" class="text-center">Este trámite no tiene pago registrado.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </article>
                        @endif
                    </div>
                </div>
            </section>
        </div>
    </div>

    {{-- Revisión por requisito: confirma decisiones y obliga observación cuando se marca No cumple. --}}
    @include('certificados.partials.show_scripts')





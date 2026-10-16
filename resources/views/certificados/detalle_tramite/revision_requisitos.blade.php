{{--
    Muestra los requisitos del trámite.
    Permite revisar, corregir y abrir el pago cuando la evidencia es PAGO.
--}}

{{-- SECCION 4: revisión de requisitos. El formulario conserva los nombres que espera el controlador. --}}
@if ($mostrarRevisionRequisitos)
    {{-- La revision interna permanece reservada hasta que el solicitante recibe una correccion. --}}
    <section
        class="tramite-grid-main tramite-section-review {{ $seguimientoCorreccionActual ? 'is-correction-mode' : '' }}">
        <div class="tramite-card">
            <div class="tramite-card-body">
                @if ($puedeRevisarRequisitos)
                    @php
                        $requisitosParaRevision = $certificado->certificadoRequisitos->values();
                        $totalRevision = $requisitosParaRevision->count();
                        $totalCumple = $requisitosParaRevision->where('cumple', 'SI')->count();
                        $totalNoCumple = $requisitosParaRevision->where('cumple', 'NO')->count();
                        $totalRevisados = $totalCumple + $totalNoCumple;
                        $totalPendientesRevision = max(0, $totalRevision - $totalRevisados);
                        $avanceRevision = $totalRevision > 0 ? round(($totalRevisados / $totalRevision) * 100) : 0;
                    @endphp

                    <form action="{{ route('seguimientos_revision_tecnica', $seguimientoTecnicoActual) }}" method="POST"
                        data-review-workbench data-review-storage-key="revision-tramite-{{ $certificado->id }}">
                        @csrf

                        <header class="review-workbench-heading">
                            <div class="review-workbench-title-row">
                                <h2>Revisión de requisitos</h2>
                                <span><strong data-review-count-reviewed>{{ $totalRevisados }}</strong> de
                                    <span data-review-count-total>{{ $totalRevision }}</span>
                                    revisados</span>
                                <div class="review-progress" role="progressbar" aria-label="Avance de revisión"
                                    aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $avanceRevision }}">
                                    <span style="width: {{ $avanceRevision }}%" data-review-progress></span>
                                </div>
                            </div>

                            <div class="review-workbench-tools">
                                <label class="review-search">
                                    <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                    <span class="sr-only">Buscar requisito</span>
                                    <input type="search" placeholder="Buscar requisito" autocomplete="off"
                                        data-review-search>
                                </label>

                                <div class="review-filters" aria-label="Filtrar requisitos">
                                    <button type="button" class="is-active" data-review-filter="all">Todos
                                        <span data-review-filter-count="all">{{ $totalRevision }}</span></button>
                                    <button type="button" data-review-filter="pending">Pendientes <span
                                            data-review-filter-count="pending">{{ $totalPendientesRevision }}</span></button>
                                    <button type="button" data-review-filter="SI">Cumple <span
                                            data-review-filter-count="SI">{{ $totalCumple }}</span></button>
                                    <button type="button" data-review-filter="NO">No cumple <span
                                            data-review-filter-count="NO">{{ $totalNoCumple }}</span></button>
                                </div>
                            </div>
                        </header>

                        @if ($requisitosParaRevision->isNotEmpty())
                            <div class="review-workbench-layout">
                                <aside class="review-requirement-list" aria-label="Requisitos del trámite">
                                    <div class="review-requirement-list-head">
                                        <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                        <strong>Requisitos</strong>
                                    </div>

                                    <div class="review-requirement-list-body" data-review-list>
                                        @foreach ($requisitosParaRevision as $requisitoCertificado)
                                            @php
                                                $configuracionRequisito = $configuracionesRequisitos->get(
                                                    $requisitoCertificado->id_requisito,
                                                );
                                                $esRequisitoCalificacion =
                                                    mb_strtoupper(
                                                        (string) $configuracionRequisito?->tipoEvidencia?->codigo,
                                                    ) === 'CALIFICACION';
                                                $ultimaRevisionCalificacion = $requisitoCertificado->revisionesRequisitos
                                                    ->where('estado', 'ACTIVO')
                                                    ->whereNotNull('calificacion_obtenida')
                                                    ->sortByDesc('id')
                                                    ->first();
                                                $calificacionActual = old(
                                                    "requisitos_revision.$loop->index.calificacion_obtenida",
                                                    $ultimaRevisionCalificacion?->calificacion_obtenida,
                                                );
                                                $esNuevaRevision =
                                                    $requisitoCertificado->estado === 'PENDIENTE_REVISION';
                                                $decisionGuardada = $esNuevaRevision
                                                    ? ''
                                                    : ($esRequisitoCalificacion &&
                                                    $calificacionActual !== null &&
                                                    $calificacionActual !== ''
                                                        ? 'SI'
                                                        : ($requisitoCertificado->cumple === 'SI'
                                                            ? 'SI'
                                                            : ($requisitoCertificado->cumple === 'NO'
                                                                ? 'NO'
                                                                : '')));
                                                $decisionActual = old(
                                                    "requisitos_revision.$loop->index.cumple",
                                                    $decisionGuardada,
                                                );
                                                $tituloRequisito =
                                                    $requisitoCertificado->requisito?->descripcion ??
                                                    'Requisito no encontrado';
                                                $estadoFiltro = $decisionActual ?: 'pending';
                                            @endphp

                                            <article class="review-requirement-item"
                                                data-review-record="{{ $requisitoCertificado->id }}"
                                                data-review-state="{{ $estadoFiltro }}"
                                                data-review-kind="{{ $esRequisitoCalificacion ? 'calificacion' : 'decision' }}"
                                                data-review-search-text="{{ \Illuminate\Support\Str::lower($tituloRequisito) }}">
                                                <input type="hidden"
                                                    name="requisitos_revision[{{ $loop->index }}][id]"
                                                    value="{{ $requisitoCertificado->id }}">
                                                <input type="hidden"
                                                    name="requisitos_revision[{{ $loop->index }}][tocado]"
                                                    value="{{ old("requisitos_revision.$loop->index.tocado", '0') }}"
                                                    data-review-touched>
                                                <input type="hidden"
                                                    name="requisitos_revision[{{ $loop->index }}][cumple]"
                                                    value="{{ $decisionActual }}" data-review-decision>

                                                <button type="button" class="review-requirement-select"
                                                    data-review-select="{{ $requisitoCertificado->id }}">
                                                    <span
                                                        class="review-requirement-number is-{{ strtolower($estadoFiltro) }}"
                                                        data-review-number-state>{{ $loop->iteration }}</span>
                                                    <span class="review-requirement-copy">
                                                        <span
                                                            class="review-requirement-name">{{ $tituloRequisito }}</span>
                                                    </span>
                                                </button>
                                            </article>
                                        @endforeach

                                        <p class="review-list-empty" data-review-empty hidden>No se
                                            encontraron requisitos.</p>
                                    </div>
                                </aside>

                                <div class="review-requirement-detail">
                                    @foreach ($requisitosParaRevision as $requisitoCertificado)
                                        @php
                                            $configuracionRequisito = $configuracionesRequisitos->get(
                                                $requisitoCertificado->id_requisito,
                                            );
                                            $esRequisitoCalificacion =
                                                mb_strtoupper(
                                                    (string) $configuracionRequisito?->tipoEvidencia?->codigo,
                                                ) === 'CALIFICACION';
                                            $ultimaRevisionCalificacion = $requisitoCertificado->revisionesRequisitos
                                                ->where('estado', 'ACTIVO')
                                                ->whereNotNull('calificacion_obtenida')
                                                ->sortByDesc('id')
                                                ->first();
                                            $calificacionActual = old(
                                                "requisitos_revision.$loop->index.calificacion_obtenida",
                                                $ultimaRevisionCalificacion?->calificacion_obtenida,
                                            );
                                            $documentoRequisito = $urlDocumentoRequisito($requisitoCertificado);
                                            $evidenciaPrincipal = $evidenciaPrincipalRequisito($requisitoCertificado);
                                            $codigoEvidencia = $esRequisitoCalificacion
                                                ? 'CALIFICACION'
                                                : $codigoEvidenciaRequisito($requisitoCertificado);
                                            $nombreEvidencia = $esRequisitoCalificacion
                                                ? 'Calificación'
                                                : $nombreEvidenciaRequisito($requisitoCertificado);
                                            $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                            $descripcionEvidencia = $esRequisitoCalificacion
                                                ? 'El técnico registra la calificación obtenida por el participante.'
                                                : $descripcionEvidenciaRequisito($requisitoCertificado);
                                            $esFilaPago = $requisitoTieneEvidencia($requisitoCertificado, 'PAGO');
                                            $comprobantePagoPrincipal = $pagoPrincipalTramite?->comprobante
                                                ? route('pagos_comprobante', $pagoPrincipalTramite)
                                                : null;
                                            $ultimaObservacion = $ultimaObservacionDeRequisito($requisitoCertificado);
                                            $esNuevaRevision = $requisitoCertificado->estado === 'PENDIENTE_REVISION';
                                            $decisionGuardada = $esNuevaRevision
                                                ? ''
                                                : ($esRequisitoCalificacion &&
                                                $calificacionActual !== null &&
                                                $calificacionActual !== ''
                                                    ? 'SI'
                                                    : ($requisitoCertificado->cumple === 'SI'
                                                        ? 'SI'
                                                        : ($requisitoCertificado->cumple === 'NO'
                                                            ? 'NO'
                                                            : '')));
                                            $decisionActual = old(
                                                "requisitos_revision.$loop->index.cumple",
                                                $decisionGuardada,
                                            );
                                            $observacionGuardada = $esNuevaRevision
                                                ? ''
                                                : $ultimaObservacion?->observacion ?? '';
                                            $observacionActual = old(
                                                "requisitos_revision.$loop->index.observacion",
                                                $observacionGuardada,
                                            );
                                            $observacionDeOtro =
                                                $ultimaObservacion &&
                                                (int) $ultimaObservacion->revisionRequisito?->id_usuario_revisor !==
                                                    (int) auth()->id();
                                            $filaObservada =
                                                $decisionActual === 'NO' ||
                                                $requisitoCertificado->estado === 'OBSERVADO';
                                            $tituloRequisito =
                                                $requisitoCertificado->requisito?->descripcion ??
                                                'Requisito no encontrado';
                                        @endphp

                                        <section class="review-detail-panel {{ $filaObservada ? 'is-observed' : '' }}"
                                            data-review-panel="{{ $requisitoCertificado->id }}"
                                            @if (!$loop->first) hidden @endif>
                                            <header class="review-detail-head">
                                                <div>
                                                    <span class="review-detail-label"><i
                                                            class="fa-regular fa-file-lines"></i>
                                                        Requisito</span>
                                                    <h3>{{ $tituloRequisito }}</h3>
                                                </div>
                                                <div class="review-detail-head-actions">
                                                    <span class="review-evidence-chip">{{ $nombreEvidencia }}</span>
                                                    <span
                                                        class="review-state is-{{ $decisionActual ? strtolower($decisionActual) : 'pending' }}"
                                                        data-review-detail-state>
                                                        <i class="{{ $decisionActual === 'SI' ? 'fa-regular fa-circle-check' : ($decisionActual === 'NO' ? 'fa-regular fa-circle-xmark' : 'fa-solid fa-circle') }}"
                                                            aria-hidden="true"></i>
                                                        <span>{{ $decisionActual === 'SI' ? 'Cumple' : ($decisionActual === 'NO' ? 'No cumple' : 'Pendiente') }}</span>
                                                    </span>
                                                </div>
                                            </header>

                                            <div class="review-detail-content">
                                                <div class="review-evidence-column">
                                                    <h4>Descripción de la evidencia</h4>
                                                    <p>{{ $descripcionEvidencia }}</p>

                                                    <div class="review-evidence-preview">
                                                        @if ($esRequisitoCalificacion)
                                                            <div class="review-evidence-reference">
                                                                <i class="fa-solid fa-graduation-cap"></i>
                                                                <span>Calificación mínima:
                                                                    {{ number_format((float) $configuracionRequisito->calificacion_minima_aprobacion, 2, ',', '.') }}</span>
                                                            </div>
                                                        @elseif ($codigoEvidencia === 'PDF' && $documentoRequisito)
                                                            <iframe
                                                                src="{{ $documentoRequisito }}#toolbar=0&navpanes=0"
                                                                title="Evidencia PDF: {{ $tituloRequisito }}"
                                                                loading="lazy"></iframe>
                                                            <div class="review-evidence-preview-actions">
                                                                <span><i class="fa-regular fa-file-pdf"></i>
                                                                    Evidencia presentada</span>
                                                                <a href="{{ $documentoRequisito }}" target="_blank"
                                                                    rel="noopener"><i
                                                                        class="fa-solid fa-up-right-from-square"></i>
                                                                    Ampliar</a>
                                                            </div>
                                                        @elseif ($codigoEvidencia === 'IMAGEN' && $documentoRequisito)
                                                            <img src="{{ $documentoRequisito }}"
                                                                alt="Evidencia del requisito: {{ $tituloRequisito }}"
                                                                loading="lazy">
                                                            <div class="review-evidence-preview-actions">
                                                                <span><i class="fa-regular fa-image"></i>
                                                                    Evidencia presentada</span>
                                                                <a href="{{ $documentoRequisito }}" target="_blank"
                                                                    rel="noopener"><i
                                                                        class="fa-solid fa-up-right-from-square"></i>
                                                                    Ampliar</a>
                                                            </div>
                                                        @elseif ($esFilaPago && $tienePagoRegistrado && $comprobantePagoPrincipal)
                                                            <iframe
                                                                src="{{ $comprobantePagoPrincipal }}#toolbar=0&navpanes=0"
                                                                title="Comprobante de pago del trámite"
                                                                loading="lazy"></iframe>
                                                            <div class="review-evidence-preview-actions">
                                                                <span><i class="fa-regular fa-file-pdf"></i>
                                                                    Comprobante registrado</span>
                                                                <a href="{{ $comprobantePagoPrincipal }}"
                                                                    target="_blank" rel="noopener">
                                                                    <i class="fa-solid fa-up-right-from-square"></i>
                                                                    Ampliar
                                                                </a>
                                                            </div>
                                                        @elseif ($esFilaPago && $tienePagoRegistrado)
                                                            <div class="review-evidence-reference">
                                                                <i class="{{ $iconoEvidencia }}"></i>
                                                                <span>{{ $nombreEvidencia }}</span>
                                                            </div>
                                                        @elseif ($esFilaPago && $puedeRegistrarPago)
                                                            <button type="button"
                                                                class="review-evidence-reference is-action"
                                                                data-open-payment-modal>
                                                                <i class="{{ $iconoEvidencia }}"></i>
                                                                <span>Este requisito necesita un pago
                                                                    registrado.</span>
                                                                <strong>Registrar pago</strong>
                                                            </button>
                                                        @elseif ($codigoEvidencia === 'TEXTO' && filled($evidenciaPrincipal?->valor))
                                                            <div class="review-evidence-text">
                                                                {{ $evidenciaPrincipal->valor }}</div>
                                                        @elseif ($documentoRequisito)
                                                            <a href="{{ $documentoRequisito }}" target="_blank"
                                                                rel="noopener"
                                                                class="review-evidence-reference is-action">
                                                                <i class="{{ $iconoEvidencia }}"></i>
                                                                <strong>{{ $nombreEvidencia }}</strong>
                                                            </a>
                                                        @else
                                                            <div class="review-evidence-reference is-empty">
                                                                <i class="{{ $iconoEvidencia }}"></i>
                                                                <span>{{ $nombreEvidencia }}</span>
                                                            </div>
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="review-decision-column">
                                                    @if ($esRequisitoCalificacion)
                                                        <h4>Calificación obtenida</h4>
                                                        <div class="review-note-field">
                                                            <input type="number"
                                                                name="requisitos_revision[{{ $loop->index }}][calificacion_obtenida]"
                                                                value="{{ $calificacionActual }}" min="0"
                                                                max="100" step="0.01" inputmode="decimal"
                                                                placeholder="Ej.: 75" data-review-calificacion
                                                                data-calificacion-minima="{{ $configuracionRequisito->calificacion_minima_aprobacion }}">
                                                            <p>
                                                                Resultado: <strong data-review-calificacion-result>
                                                                    @if ($calificacionActual === null || $calificacionActual === '')
                                                                        Pendiente
                                                                    @elseif ((float) $calificacionActual >= (float) $configuracionRequisito->calificacion_minima_aprobacion)
                                                                        Aprobado
                                                                    @else
                                                                        Participación
                                                                    @endif
                                                                </strong>
                                                            </p>
                                                        </div>
                                                        <p class="review-decision-help">Una calificación
                                                            registrada completa el requisito. La
                                                            calificación mínima define el resultado del
                                                            certificado.</p>
                                                    @else
                                                        <h4>¿Cumple el requisito?</h4>
                                                        <div class="review-decision-options">
                                                            <button type="button"
                                                                class="is-yes {{ $decisionActual === 'SI' ? 'is-selected' : '' }}"
                                                                value="SI" data-review-choice><i
                                                                    class="fa-regular fa-circle-check"></i>
                                                                Sí</button>
                                                            <button type="button"
                                                                class="is-no {{ $decisionActual === 'NO' ? 'is-selected' : '' }}"
                                                                value="NO" data-review-choice><i
                                                                    class="fa-regular fa-circle-xmark"></i>
                                                                No</button>
                                                            <button type="button"
                                                                class="is-pending {{ blank($decisionActual) ? 'is-selected' : '' }}"
                                                                value="" data-review-choice><i
                                                                    class="fa-regular fa-clock"></i>
                                                                Pendiente</button>
                                                        </div>

                                                        <p class="review-decision-help" data-review-decision-help>
                                                            {{ $decisionActual === 'NO'
                                                                ? 'Indique qué debe corregir el solicitante.'
                                                                : ($decisionActual === 'SI'
                                                                    ? 'El requisito quedará marcado como cumplido.'
                                                                    : 'El requisito está pendiente de revisión.') }}
                                                        </p>

                                                        <div class="review-observation-field" data-review-observation
                                                            @if ($decisionActual !== 'NO') hidden @endif>
                                                            <label
                                                                for="revision_observacion_{{ $requisitoCertificado->id }}">Motivo
                                                                de incumplimiento <span>*</span></label>
                                                            <textarea id="revision_observacion_{{ $requisitoCertificado->id }}"
                                                                name="requisitos_revision[{{ $loop->index }}][observacion]" maxlength="1000"
                                                                placeholder="Explique qué debe corregir el solicitante." data-observation-input
                                                                @if ($decisionActual !== 'NO') disabled @endif>{{ $observacionActual }}</textarea>
                                                            @if ($observacionDeOtro)
                                                                <small>Al guardar se registrará una nueva
                                                                    observación.</small>
                                                            @endif
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </section>
                                    @endforeach
                                </div>
                            </div>

                            <div class="review-workbench-footer">
                                <a href="{{ url()->previous() }}" class="tramite-btn tramite-btn-muted">
                                    <i class="fa-solid fa-arrow-left"></i>
                                    Salir sin guardar
                                </a>

                                @if ($certificado->estado === 'OBSERVADO')
                                    <button type="button" class="tramite-btn tramite-btn-primary" disabled><i
                                            class="fa-regular fa-floppy-disk"></i> Guardar
                                        revisión</button>
                                @else
                                    <button type="submit" class="tramite-btn tramite-btn-primary"
                                        data-review-save-next><i class="fa-regular fa-floppy-disk"></i>
                                        Guardar revisión</button>
                                @endif

                                @if ($certificado->estado !== 'OBSERVADO' && $puedeNotificarCorreccion)
                                    <button type="button" class="tramite-btn tramite-btn-notify"
                                        data-open-correction-recipient-modal>
                                        <i class="fa-solid fa-paper-plane"></i>
                                        Notificar solicitante
                                    </button>
                                @endif

                                @if ($puedeFinalizarTramite)
                                    <button type="button" class="tramite-btn tramite-btn-finish"
                                        data-open-finalization-modal>
                                        <i class="fa-solid fa-circle-check"></i>
                                        Finalizar trámite
                                    </button>
                                @endif
                            </div>
                        @else
                            <div class="review-workbench-empty">Este trámite no tiene requisitos
                                registrados.</div>
                        @endif

                        @if ($certificado->estado === 'OBSERVADO')
                            <div class="tramite-actions-row">
                                <button type="button" class="tramite-btn tramite-btn-muted" disabled>
                                    <i class="fa-solid fa-arrow-right"></i>
                                    Derivar
                                </button>
                                <span class="tramite-warning-box">
                                    <i class="fa-solid fa-triangle-exclamation"></i>
                                    Trámite observado: no se puede derivar ni continuar revisión hasta que
                                    el solicitante corrija.
                                </span>
                            </div>
                        @endif
                    </form>
                    @if ($puedeNotificarCorreccion)
                        <form id="form-notificar-correccion-v2"
                            action="{{ route('seguimientos_notificar_correccion', $seguimientoTecnicoActual) }}"
                            method="POST" data-prevent-double-submit data-loading-button="Notificando...">
                            @csrf
                        </form>

                        <div class="tramite-modal" data-correction-recipient-modal aria-hidden="true">
                            <div class="tramite-modal-backdrop" data-close-correction-recipient-modal>
                            </div>
                            <section class="tramite-modal-panel tramite-modal-panel-correction" role="dialog"
                                aria-modal="true" aria-labelledby="tituloDestinoCorreccion">
                                <div class="tramite-modal-head">
                                    <div>
                                        <h2 id="tituloDestinoCorreccion" class="tramite-card-title">
                                            <i class="fa-solid fa-paper-plane"></i>
                                            Notificar solicitante
                                        </h2>
                                        <p>Seleccione quién corregirá los requisitos observados.</p>
                                    </div>
                                    <button type="button" class="tramite-modal-close"
                                        data-close-correction-recipient-modal aria-label="Cerrar">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                @php
                                    $destinatarioSeleccionado = $destinatariosCorreccion->firstWhere(
                                        'id',
                                        $idDestinatarioCorreccion,
                                    );
                                @endphp
                                <div class="cert-technical-field" data-correction-recipient-selector>
                                    <label class="cert-show-label"
                                        for="form_id_usuario_responsable_correccion">Responsable de la
                                        corrección</label>
                                    <input type="hidden" id="form_id_usuario_responsable_correccion"
                                        name="form_id_usuario_responsable_correccion"
                                        form="form-notificar-correccion-v2" value="{{ $idDestinatarioCorreccion }}"
                                        data-correction-recipient-value>
                                    <button type="button" class="cert-technical-control"
                                        data-correction-recipient-toggle @disabled($destinatarioCorreccionBloqueado)
                                        aria-expanded="false">
                                        <span class="cert-technical-avatar"><i class="fa-regular fa-user"></i></span>
                                        <span class="cert-technical-selected">
                                            <span class="cert-technical-selected-name"
                                                data-correction-recipient-name>{{ $destinatarioSeleccionado['nombre'] ?? 'Seleccione una persona' }}</span>
                                            <span class="cert-technical-selected-help"
                                                data-correction-recipient-type>{{ $destinatarioSeleccionado['tipo'] ?? '' }}</span>
                                        </span>
                                        <i class="fa-solid fa-chevron-down cert-technical-chevron"></i>
                                    </button>
                                    <div class="cert-correction-combobox" data-correction-recipient-combobox hidden>
                                        <div class="cert-technical-search cert-technical-search-control"
                                            data-correction-recipient-search-control>
                                            <i class="fa-solid fa-magnifying-glass"></i>
                                            <input type="search" data-correction-recipient-search
                                                placeholder="Buscar representante legal o tramitador">
                                        </div>
                                        <div class="cert-technical-dropdown" data-correction-recipient-menu>
                                            <div class="cert-technical-options">
                                                @foreach ($destinatariosCorreccion as $destinatario)
                                                    @php
                                                        $esDestinatarioSeleccionado =
                                                            (string) $destinatario['id'] ===
                                                            (string) $idDestinatarioCorreccion;
                                                    @endphp
                                                    <button type="button" @class([
                                                        'cert-technical-option',
                                                        'is-selected' => $esDestinatarioSeleccionado,
                                                    ])
                                                        data-correction-recipient-option
                                                        data-value="{{ $destinatario['id'] }}"
                                                        data-nombre="{{ $destinatario['nombre'] }}"
                                                        data-tipo="{{ $destinatario['tipo'] }}"
                                                        data-busqueda="{{ $destinatario['busqueda'] }}"
                                                        aria-selected="{{ $esDestinatarioSeleccionado ? 'true' : 'false' }}">
                                                        <span class="cert-technical-option-icon">
                                                            <i
                                                                class="fa-solid {{ $destinatario['tipo'] === 'Representante legal' ? 'fa-user-tie' : 'fa-user-check' }}"></i>
                                                        </span>
                                                        <span class="cert-technical-option-main">
                                                            <strong>{{ $destinatario['nombre'] }}</strong>
                                                            <span>{{ $destinatario['tipo'] }}</span>
                                                        </span>
                                                        <span class="cert-correction-option-check" aria-hidden="true">
                                                            <i class="fa-solid fa-check"></i>
                                                        </span>
                                                    </button>
                                                @endforeach
                                                <div class="cert-technical-empty is-hidden"
                                                    data-correction-recipient-empty>No se encontraron
                                                    resultados.</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                @if ($destinatarioCorreccionBloqueado)
                                    <p class="mt-2 text-sm text-slate-600">La persona natural es
                                        beneficiario y tramitador del mismo trámite.</p>
                                @endif

                                <div class="tramite-actions-row mt-5">
                                    <button type="button" class="tramite-btn tramite-btn-muted"
                                        data-close-correction-recipient-modal>Cancelar</button>
                                    <button type="submit" form="form-notificar-correccion-v2"
                                        class="tramite-btn tramite-btn-notify">
                                        <i class="fa-solid fa-paper-plane"></i>
                                        Notificar solicitante
                                    </button>
                                </div>
                            </section>
                        </div>
                    @endif
                    @if ($puedeFinalizarTramite)
                        <form id="form-finalizar-tramite"
                            action="{{ route('seguimientos_finalizar_tramite', $seguimientoTecnicoActual) }}"
                            method="POST" data-prevent-double-submit data-loading-button="Finalizando...">
                            @csrf
                            @if ($tramiteRequiereHabilitarTramitador)
                                <input type="hidden" name="form_aceptar_tramitador" id="aceptar-tramitador"
                                    value="">
                            @endif
                        </form>

                        <div class="tramite-modal {{ $errors->has('form_descripcion_final') ? 'is-open' : '' }}"
                            data-finalization-modal @if ($errors->has('form_descripcion_final')) data-open-on-error="1" @endif
                            aria-hidden="{{ $errors->has('form_descripcion_final') ? 'false' : 'true' }}">
                            <div class="tramite-modal-backdrop" data-close-finalization-modal></div>
                            <section class="tramite-modal-panel tramite-modal-panel-finalization" role="dialog"
                                aria-modal="true" aria-labelledby="tituloFinalizarTramite">
                                <div class="tramite-modal-head">
                                    <div>
                                        <h2 id="tituloFinalizarTramite" class="tramite-card-title">
                                            <i class="fa-solid fa-circle-check"></i>
                                            Finalizar trámite
                                        </h2>
                                        <p>Registre la descripción final antes de cerrar el trámite.</p>
                                    </div>
                                    <button type="button" class="tramite-modal-close" data-close-finalization-modal
                                        aria-label="Cerrar">
                                        <i class="fa-solid fa-xmark"></i>
                                    </button>
                                </div>

                                <div class="tramite-finalization-field">
                                    <label for="form_descripcion_final">Descripción final del trámite
                                        <span>*</span></label>
                                    <textarea id="form_descripcion_final" name="form_descripcion_final" form="form-finalizar-tramite" maxlength="2000"
                                        rows="5" required placeholder="Escriba el resultado final del trámite.">{{ old('form_descripcion_final') }}</textarea>
                                    <small>Campo obligatorio. Máximo 2000 caracteres.</small>
                                    @error('form_descripcion_final')
                                        <p class="tramite-finalization-error">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-actions-row mt-5">
                                    <button type="button" class="tramite-btn tramite-btn-muted"
                                        data-close-finalization-modal>Cancelar</button>
                                    <button type="{{ $tramiteRequiereHabilitarTramitador ? 'button' : 'submit' }}"
                                        form="form-finalizar-tramite" class="tramite-btn tramite-btn-finish"
                                        @if ($tramiteRequiereHabilitarTramitador) data-confirmar-tramitador @endif>
                                        <i class="fa-regular fa-floppy-disk"></i>
                                        Guardar y finalizar trámite
                                    </button>
                                </div>
                            </section>
                        </div>
                    @endif
                @elseif ($seguimientoCorreccionActual)
                    {{-- El solicitante corrige cada requisito segun el tipo de evidencia configurado. --}}
                    <form id="form-correccion-requisitos-v2"
                        action="{{ route('seguimientos_reenviar_correccion', $seguimientoCorreccionActual) }}"
                        method="POST" enctype="multipart/form-data" data-prevent-double-submit
                        data-loading-button="Devolviendo...">
                        @csrf

                        <div class="cert-show-table-wrap tramite-table-wrap">
                            <table
                                class="cert-show-table cert-review-table tramite-table tramite-requirements-table cert-correction-table">
                                <thead>
                                    <tr>
                                        <th class="cert-review-col-number">#</th>
                                        <th>Requisito</th>
                                        <th>Tipo de evidencia</th>
                                        <th>Observación actual</th>
                                        <th>Corrección</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($certificado->certificadoRequisitos->where('cumple', 'NO') as $requisitoCertificado)
                                        @php
                                            $codigoEvidencia = $codigoEvidenciaRequisito($requisitoCertificado);
                                            $nombreEvidencia = $nombreEvidenciaRequisito($requisitoCertificado);
                                            $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                            $ultimaObservacionCorreccion = $ultimaObservacionDeRequisito(
                                                $requisitoCertificado,
                                            );
                                            $evidenciaPrincipalCorreccion = $requisitoCertificado->evidenciasRequisitos
                                                ->sortByDesc('id')
                                                ->first();
                                            $valorEvidenciaCorreccion = $evidenciaPrincipalCorreccion?->valor;
                                            $tamanioMaximoCorreccion =
                                                (int) ($evidenciaPrincipalCorreccion?->tipoEvidencia
                                                    ?->tamanio_maximo_mb ?? 0);
                                            $rutaProductoCorreccion = route('productos_create', [
                                                'form_id_certificado' => $certificado->id,
                                                'form_id_importador_persona' => $certificado->id_persona_beneficiario,
                                                'return_to' => request()->fullUrl(),
                                            ]);
                                        @endphp
                                        <tr class="cert-requirement-row">
                                            <td>{{ $loop->iteration }}</td>
                                            <td>
                                                <strong class="block text-slate-800">
                                                    {{ $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado' }}
                                                </strong>
                                            </td>
                                            <td>
                                                <div class="cert-correction-document-cell">
                                                    <span class="cert-correction-evidence-chip">
                                                        <i class="{{ $iconoEvidencia }}"></i>
                                                        {{ $nombreEvidencia }}
                                                    </span>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="cert-correction-observation-text">
                                                    {{ $ultimaObservacionCorreccion?->observacion ?? 'Sin observación' }}
                                                </span>
                                            </td>
                                            <td>
                                                @if (in_array($codigoEvidencia, ['PDF', 'IMAGEN'], true))
                                                    @php
                                                        $idArchivoCorreccion =
                                                            'documento_correccion_' . $requisitoCertificado->id;
                                                        $nombreArchivoVacio =
                                                            $codigoEvidencia === 'IMAGEN'
                                                                ? 'Sin imagen seleccionada'
                                                                : 'Sin PDF seleccionado';
                                                        $detalleArchivoVacio =
                                                            $tamanioMaximoCorreccion > 0
                                                                ? 'Máximo ' . $tamanioMaximoCorreccion . ' MB'
                                                                : ($codigoEvidencia === 'IMAGEN'
                                                                    ? 'Archivo de imagen'
                                                                    : 'Archivo PDF');
                                                    @endphp
                                                    <div class="cert-correction-file-control @error("documentos_correccion.{$requisitoCertificado->id}") is-invalid @enderror"
                                                        data-correction-file-control>
                                                        <input id="{{ $idArchivoCorreccion }}"
                                                            class="cert-correction-file-input" type="file"
                                                            name="documentos_correccion[{{ $requisitoCertificado->id }}]"
                                                            accept="{{ $codigoEvidencia === 'IMAGEN' ? 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp' : 'application/pdf,.pdf' }}"
                                                            data-correction-file-input>
                                                        <div class="cert-correction-file-info">
                                                            <i
                                                                class="{{ $codigoEvidencia === 'IMAGEN' ? 'fa-regular fa-image' : 'fa-regular fa-file-pdf' }}"></i>
                                                            <div>
                                                                <strong data-correction-file-name
                                                                    data-empty-name="{{ $nombreArchivoVacio }}">{{ $nombreArchivoVacio }}</strong>
                                                                <span data-correction-file-status
                                                                    data-empty-status="{{ $detalleArchivoVacio }}">{{ $detalleArchivoVacio }}</span>
                                                            </div>
                                                        </div>
                                                        <div class="cert-correction-file-actions">
                                                            <label for="{{ $idArchivoCorreccion }}"
                                                                class="cert-correction-file-button is-select">
                                                                <i class="fa-solid fa-upload"></i>
                                                                Seleccionar
                                                            </label>
                                                            <button type="button"
                                                                class="cert-correction-file-button is-view"
                                                                data-correction-file-view disabled>
                                                                <i class="fa-regular fa-eye"></i>
                                                                Ver
                                                            </button>
                                                            <button type="button"
                                                                class="cert-correction-file-button is-remove"
                                                                data-correction-file-clear disabled>
                                                                <i class="fa-regular fa-trash-can"></i>
                                                                Quitar
                                                            </button>
                                                        </div>
                                                    </div>
                                                    @error("documentos_correccion.{$requisitoCertificado->id}")
                                                        <p class="cert-correction-error">{{ $message }}
                                                        </p>
                                                    @enderror
                                                @elseif ($codigoEvidencia === 'TEXTO')
                                                    <textarea name="textos_correccion[{{ $requisitoCertificado->id }}]" class="cert-correction-textarea" rows="3"
                                                        placeholder="Escriba la corrección solicitada">{{ old("textos_correccion.{$requisitoCertificado->id}", $valorEvidenciaCorreccion) }}</textarea>
                                                    @error("textos_correccion.{$requisitoCertificado->id}")
                                                        <p class="cert-correction-error">{{ $message }}
                                                        </p>
                                                    @enderror
                                                @elseif ($codigoEvidencia === 'PRODUCTO')
                                                    <a href="{{ $rutaProductoCorreccion }}"
                                                        class="cert-correction-action">
                                                        <i class="fa-solid fa-box"></i>
                                                        Registrar producto
                                                    </a>
                                                    @error("requisito_producto_{$requisitoCertificado->id}")
                                                        <p class="cert-correction-error">{{ $message }}
                                                        </p>
                                                    @enderror
                                                @elseif ($codigoEvidencia === 'PAGO')
                                                    <span class="cert-correction-note">
                                                        No requiere archivo en este requisito.
                                                    </span>
                                                @elseif ($codigoEvidencia === 'CERTIFICADO')
                                                    <span class="cert-correction-note">
                                                        El sistema verificará el certificado requerido.
                                                    </span>
                                                @elseif ($codigoEvidencia === 'PRESENCIAL')
                                                    <span class="cert-correction-note">
                                                        La corrección se registra en atención presencial.
                                                    </span>
                                                @else
                                                    <span class="cert-correction-note">
                                                        Revise la observación del técnico.
                                                    </span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Sin requisitos
                                                pendientes.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="tramite-actions-row tramite-correction-actions mt-4">
                            <a href="{{ route('seguimientos_mis_tramites_beneficiario') }}"
                                class="tramite-btn tramite-btn-muted">
                                <i class="fa-solid fa-xmark"></i>
                                Salir sin guardar
                            </a>
                            <button type="submit" name="form_accion_correccion" value="enviar"
                                class="tramite-btn tramite-btn-primary"
                                onclick="return confirm('El trámite volverá al mismo revisor que envió la observación. ¿Desea continuar?')">
                                <i class="fa-solid fa-reply"></i>
                                Enviar al revisor
                            </button>
                        </div>
                    </form>
                @else
                    <div class="cert-show-table-wrap tramite-table-wrap">
                        <table
                            class="cert-show-table cert-review-table tramite-table tramite-requirements-table is-readonly">
                            <thead>
                                <tr>
                                    <th class="cert-review-col-number">#</th>
                                    <th>Requisito</th>
                                    <th>Tipo de evidencia</th>
                                    <th class="cert-review-col-result">Cumple</th>
                                    <th>Estado</th>
                                    <th class="cert-review-col-observation">Observación</th>
                                    <th>Última evidencia</th>
                                    <th class="cert-review-col-history">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($certificado->certificadoRequisitos as $requisitoCertificado)
                                    @php
                                        $ultimaObservacion = $ultimaObservacionDeRequisito($requisitoCertificado);
                                        $codigoEvidencia = $codigoEvidenciaRequisito($requisitoCertificado);
                                        $nombreEvidencia = $nombreEvidenciaRequisito($requisitoCertificado);
                                        $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                        $evidenciaPrincipal = $evidenciaPrincipalRequisito($requisitoCertificado);
                                        $documentoRequisito = $urlDocumentoRequisito($requisitoCertificado);
                                        $esEvidenciaPago = $requisitoTieneEvidencia($requisitoCertificado, 'PAGO');
                                        $comprobantePago =
                                            $esEvidenciaPago && $pagoPrincipalTramite?->comprobante
                                                ? route('pagos_comprobante', $pagoPrincipalTramite)
                                                : null;
                                        $observacionInterna =
                                            $esSolicitante && $requisitoCertificado->estado === 'REVISION_OBSERVADA';
                                        $textoCumple =
                                            $observacionInterna ||
                                            $requisitoCertificado->estado === 'PENDIENTE_REVISION'
                                                ? 'Pendiente'
                                                : ($requisitoCertificado->cumple === 'SI'
                                                    ? 'Cumple'
                                                    : 'Observado');
                                        $claseCumple = match ($textoCumple) {
                                            'Cumple' => 'tramite-pill-ok',
                                            'Observado' => 'tramite-pill-danger',
                                            default => 'tramite-pill-warn',
                                        };
                                        $claseEstadoActual = $claseEstadoRequisito($requisitoCertificado->estado);
                                        $textoCumpleCorto = match ($textoCumple) {
                                            'Cumple' => 'Sí',
                                            'Observado' => 'No',
                                            default => 'Pendiente',
                                        };
                                        $textoObservacionRequisito =
                                            $requisitoCertificado->estado === 'PENDIENTE_REVISION'
                                                ? 'Pendiente de revisión'
                                                : ($observacionInterna
                                                    ? 'Pendiente de notificación'
                                                    : $ultimaObservacion?->observacion ?? 'Sin observación');
                                    @endphp
                                    <tr
                                        class="cert-requirement-row {{ $textoCumple === 'Observado' || $requisitoCertificado->estado === 'OBSERVADO' ? 'is-observed tramite-row-observed' : '' }}">
                                        <td>{{ $loop->iteration }}</td>
                                        <td>{{ $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado' }}
                                        </td>
                                        <td>
                                            <span class="cert-correction-evidence-chip">
                                                <i class="{{ $iconoEvidencia }}"></i>
                                                {{ $nombreEvidencia }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="tramite-pill tramite-status-chip {{ $claseCumple }}">
                                                {{ $textoCumpleCorto }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="tramite-pill tramite-status-chip {{ $claseEstadoActual }}">
                                                {{ $textoEstadoRequisito($requisitoCertificado->estado) }}
                                            </span>
                                        </td>
                                        <td>{{ $textoObservacionRequisito }}</td>
                                        <td>
                                            @if ($codigoEvidencia === 'PDF' && $documentoRequisito)
                                                <a href="{{ $documentoRequisito }}" target="_blank" rel="noopener"
                                                    class="cert-latest-evidence-link is-pdf">Ver PDF</a>
                                            @elseif ($codigoEvidencia === 'IMAGEN' && $documentoRequisito)
                                                <a href="{{ $documentoRequisito }}" target="_blank" rel="noopener"
                                                    class="cert-latest-evidence-link is-image">Ver
                                                    imagen</a>
                                            @elseif ($codigoEvidencia === 'TEXTO' && filled($evidenciaPrincipal?->valor))
                                                <span class="cert-latest-evidence-text"
                                                    title="{{ $evidenciaPrincipal->valor }}">{{ $evidenciaPrincipal->valor }}</span>
                                            @elseif ($codigoEvidencia === 'PAGO' && $tienePagoRegistrado)
                                                <span class="cert-latest-evidence-status is-payment">Pago
                                                    registrado</span>
                                                @if ($comprobantePago)
                                                    <a href="{{ $comprobantePago }}" target="_blank" rel="noopener"
                                                        class="cert-latest-evidence-link is-pdf">Ver
                                                        PDF</a>
                                                @endif
                                            @elseif ($codigoEvidencia === 'PRODUCTO' && $tieneProductoRegistrado)
                                                <span class="cert-latest-evidence-status is-product">Producto
                                                    registrado</span>
                                            @else
                                                <span class="cert-latest-evidence-empty">Sin
                                                    evidencia</span>
                                            @endif
                                        </td>

                                        <td>
                                            <div class="flex flex-wrap items-center gap-2">

                                                {{-- Abre el historial del requisito actual. --}}
                                                <button type="button" class="cert-history-button"
                                                    data-requirement-history-button
                                                    data-requirement-id="{{ $requisitoCertificado->id }}"
                                                    title="Ver historial"
                                                    aria-label="Ver historial del requisito {{ $loop->iteration }}">
                                                    Historial
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">Este trámite no tiene
                                            requisitos registrados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    @if ($certificado->estado === 'OBSERVADO' && !$esSolicitante)
                        @if ($puedeRegistrarCorreccionRecibida)
                            {{-- Corrección presencial: no exige PDF, solo registra que INSO recibió la corrección. --}}
                            <form
                                action="{{ route('seguimientos_registrar_correccion_recibida', $seguimientoAtencionActual) }}"
                                method="POST" class="tramite-actions-row" data-confirm-received-correction>
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
                                    Trámite observado: no se puede derivar ni continuar revisión hasta que
                                    el solicitante corrija.
                                </span>
                            </div>
                        @endif
                    @endif
                @endif
            </div>
        </div>

        {{-- El historial técnico no se muestra durante la corrección del solicitante. --}}
        @unless ($seguimientoCorreccionActual)
            <aside class="tramite-card tramite-history-panel">
                <div class="tramite-card-head">
                    <h2 class="tramite-card-title">Historial del requisito seleccionado</h2>
                </div>
                <div class="tramite-card-body">
                    <p class="cert-requirement-history-subtitle" data-requirement-history-title>
                        Seleccione un requisito para ver observaciones, correcciones y decisiones.
                    </p>
                    <div class="tramite-history-list" data-requirement-history-list>
                        <div class="cert-history-empty">El historial aparece aquí al seleccionar un requisito.
                        </div>
                    </div>
                </div>
            </aside>
        @endunless
    </section>
@endif

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

        // Identifica cómo actúa la persona dentro de la empresa beneficiaria del trámite.
        $tipoRelacionTramitador = function ($personaTramitador, $personaBeneficiaria) {
            $empresa = $personaBeneficiaria?->empresa;

            if (! $empresa) {
                return 'Solicitante';
            }

            if (! $personaTramitador) {
                return 'Relación no identificada';
            }

            $roles = \App\Models\Responsable::query()
                ->with('rol:id,slug')
                ->where('id_empresa', $empresa->id)
                ->where('id_persona', $personaTramitador->id)
                ->whereIn('estado', ['1', 'ACTIVO'])
                ->whereHas('rol', fn ($rol) => $rol->whereIn('slug', ['solicitante', 'tramitador']))
                ->get()
                ->pluck('rol.slug')
                ->filter()
                ->unique();

            $esRepresentante = $roles->contains('solicitante');
            $esTramitador = $roles->contains('tramitador');

            return match (true) {
                $esRepresentante => 'Representante legal',
                $esTramitador => 'Tramitador',
                default => 'Relación no identificada',
            };
        };

        $tipoRelacionTramitadorActual = $tipoRelacionTramitador(
            $certificado->tramitador,
            $certificado->beneficiario
        );

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

        // Muestra el nombre real vinculado a la cuenta, sea funcionario o solicitante.
        $nombreUsuario = function ($usuario, string $fallback = 'Sin usuario') {
            if (!$usuario) {
                return $fallback;
            }

            $nombreCompleto = trim($usuario->nombreCompleto());

            return $nombreCompleto !== '' ? $nombreCompleto : $fallback;
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

        // Para el inicio del trámite distingue al personal INSO de quien actúa por la empresa.
        $rolUsuarioEnTramite = function ($usuario) use ($cargoUsuario, $tipoRelacionTramitador, $certificado) {
            if (!$usuario) {
                return 'Sin rol identificado';
            }

            $usuario->loadMissing(['funcionario.cargos', 'persona.natural', 'persona.empresa']);

            if ($usuario->funcionario) {
                return $cargoUsuario($usuario);
            }

            if ($usuario->persona) {
                return $tipoRelacionTramitador($usuario->persona, $certificado->beneficiario);
            }

            return 'Sin rol identificado';
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
                'PENDIENTE_REVISION' => 'tramite-pill-info',
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
                ->filter(fn ($evidencia) => in_array($evidencia->tipoEvidencia?->codigo, ['PDF', 'IMAGEN', 'PAGO'], true))
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

        // Localiza el PDF o imagen usando la misma dirección desde la que se abrió el sistema.
        // Así el archivo no depende del dominio configurado en APP_URL.
        $urlDocumentoRequisito = function ($requisitoCertificado) {
            $evidencia = $requisitoCertificado->evidenciasRequisitos
                ->filter(fn ($item) => in_array($item->tipoEvidencia?->codigo, ['PDF', 'IMAGEN'], true))
                ->sortByDesc('id')
                ->first();

            if ($evidencia?->valor) {
                if (\Illuminate\Support\Str::startsWith($evidencia->valor, ['http://', 'https://'])) {
                    return $evidencia->valor;
                }

                $rutaPublica = ltrim($evidencia->valor, '/');

                if (! \Illuminate\Support\Str::startsWith($rutaPublica, 'storage/')) {
                    $rutaPublica = 'storage/' . $rutaPublica;
                }

                return request()->getSchemeAndHttpHost()
                    . request()->getBaseUrl()
                    . '/' . $rutaPublica;
            }

            $rutaPublica = 'storage/documentos/requisitos_certificados/' . $requisitoCertificado->id . '/documento.pdf';

            return file_exists(public_path($rutaPublica)) ? asset($rutaPublica) : null;
        };

        // Un trámite puede incluir varios productos. Se agrupan por producto para no repetir la misma ficha.
        $registrosPorProducto = $certificado->registros->groupBy(
            fn ($registro) => $registro->producto?->id ? 'producto_' . $registro->producto->id : 'registro_' . $registro->id,
        );
        // El historial tecnico no se envia al navegador del solicitante antes de notificar una correccion.
        $historialRequisitos = $mostrarRevisionRequisitos
            ? $certificado->certificadoRequisitos->mapWithKeys(function ($requisitoCertificado) use ($observacionesDeRequisito, $ultimaRevisionRequisito, $nombreUsuario, $cargoUsuario) {
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
            })
            : collect();

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
        // El pago se registra una sola vez, pero el personal autorizado puede corregir sus datos.
        $pagoPrincipalTramite = $certificado->pagos->first();
        $tienePagoRegistrado = filled($pagoPrincipalTramite?->id);
        $puedeRegistrarPago = $requierePagoTramite
            && !$tienePagoRegistrado
            && !$esSolicitante
            && (auth()->user()?->puede('pagos.validar') ?? false)
            && ($puedeAsignarTecnico || $puedeRevisarRequisitos);
        $puedeEditarPago = $tienePagoRegistrado
            && !$esSolicitante
            && (auth()->user()?->puede('pagos.validar') ?? false);
        $idPagoSolicitado = old('form_id_pago', request('editar_pago'));
        $pagoEditando = $puedeEditarPago && (int) $idPagoSolicitado === (int) $pagoPrincipalTramite?->id
            ? $pagoPrincipalTramite
            : null;
        $modalPagoEnEdicion = filled($pagoEditando?->id);
        $abrirModalPago = ($puedeRegistrarPago || $puedeEditarPago)
            && collect([
                'form_id_procedencia_pago',
                'form_tipo_pago',
                'form_fecha_pago',
                'form_monto_pago',
                'form_factura_pago',
                'form_comprobante_pago',
                'form_id_certificado',
                'form_id_pago',
            ])->contains(fn ($campo) => $errors->has($campo));
        $abrirModalPago = $abrirModalPago || $modalPagoEnEdicion;

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
                        <span class="tramite-summary-role">{{ $tipoRelacionTramitadorActual }}</span>
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

            {{-- SECCION 4: revisión de requisitos. El formulario conserva los nombres que espera el controlador. --}}
            @if ($mostrarRevisionRequisitos)
            {{-- La revision interna permanece reservada hasta que el solicitante recibe una correccion. --}}
            <section class="tramite-grid-main tramite-section-review">
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

                            <form action="{{ route('seguimientos_revision_tecnica', $seguimientoTecnicoActual) }}"
                                method="POST"
                                data-review-workbench
                                data-review-storage-key="revision-tramite-{{ $certificado->id }}">
                                @csrf

                                <header class="review-workbench-heading">
                                    <div class="review-workbench-title-row">
                                        <h2>Revisión de requisitos</h2>
                                        <span><strong data-review-count-reviewed>{{ $totalRevisados }}</strong> de <span data-review-count-total>{{ $totalRevision }}</span> revisados</span>
                                        <div class="review-progress" role="progressbar" aria-label="Avance de revisión" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $avanceRevision }}">
                                            <span style="width: {{ $avanceRevision }}%" data-review-progress></span>
                                        </div>
                                    </div>

                                    <div class="review-workbench-tools">
                                        <label class="review-search">
                                            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>
                                            <span class="sr-only">Buscar requisito</span>
                                            <input type="search" placeholder="Buscar requisito" autocomplete="off" data-review-search>
                                        </label>

                                        <div class="review-filters" aria-label="Filtrar requisitos">
                                            <button type="button" class="is-active" data-review-filter="all">Todos <span data-review-filter-count="all">{{ $totalRevision }}</span></button>
                                            <button type="button" data-review-filter="pending">Pendientes <span data-review-filter-count="pending">{{ $totalPendientesRevision }}</span></button>
                                            <button type="button" data-review-filter="SI">Cumple <span data-review-filter-count="SI">{{ $totalCumple }}</span></button>
                                            <button type="button" data-review-filter="NO">No cumple <span data-review-filter-count="NO">{{ $totalNoCumple }}</span></button>
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
                                                        $decisionActual = old(
                                                            "requisitos_revision.$loop->index.cumple",
                                                            $requisitoCertificado->cumple === 'SI'
                                                                ? 'SI'
                                                                : ($requisitoCertificado->cumple === 'NO' ? 'NO' : '')
                                                        );
                                                        $tituloRequisito = $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado';
                                                        $estadoFiltro = $decisionActual ?: 'pending';
                                                    @endphp

                                                    <article class="review-requirement-item"
                                                        data-review-record="{{ $requisitoCertificado->id }}"
                                                        data-review-state="{{ $estadoFiltro }}"
                                                        data-review-search-text="{{ \Illuminate\Support\Str::lower($tituloRequisito) }}">
                                                        <input type="hidden" name="requisitos_revision[{{ $loop->index }}][id]" value="{{ $requisitoCertificado->id }}">
                                                        <input type="hidden" name="requisitos_revision[{{ $loop->index }}][tocado]" value="{{ old("requisitos_revision.$loop->index.tocado", '0') }}" data-review-touched>
                                                        <input type="hidden" name="requisitos_revision[{{ $loop->index }}][cumple]" value="{{ $decisionActual }}" data-review-decision>

                                                        <button type="button" class="review-requirement-select" data-review-select="{{ $requisitoCertificado->id }}">
                                                            <span class="review-requirement-number is-{{ strtolower($estadoFiltro) }}" data-review-number-state>{{ $loop->iteration }}</span>
                                                            <span class="review-requirement-copy">
                                                                <span class="review-requirement-name">{{ $tituloRequisito }}</span>
                                                            </span>
                                                        </button>
                                                    </article>
                                                @endforeach

                                                <p class="review-list-empty" data-review-empty hidden>No se encontraron requisitos.</p>
                                            </div>
                                        </aside>

                                        <div class="review-requirement-detail">
                                            @foreach ($requisitosParaRevision as $requisitoCertificado)
                                                @php
                                                    $documentoRequisito = $urlDocumentoRequisito($requisitoCertificado);
                                                    $evidenciaPrincipal = $evidenciaPrincipalRequisito($requisitoCertificado);
                                                    $codigoEvidencia = $codigoEvidenciaRequisito($requisitoCertificado);
                                                    $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                                    $descripcionEvidencia = $descripcionEvidenciaRequisito($requisitoCertificado);
                                                    $esFilaPago = $requisitoTieneEvidencia($requisitoCertificado, 'PAGO');
                                                    $comprobantePagoPrincipal = $pagoPrincipalTramite?->comprobante
                                                        ? route('pagos_comprobante', $pagoPrincipalTramite)
                                                        : null;
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
                                                    $observacionDeOtro = $ultimaObservacion
                                                        && (int) ($ultimaObservacion->revisionRequisito?->id_usuario_revisor) !== (int) auth()->id();
                                                    $filaObservada = $decisionActual === 'NO' || $requisitoCertificado->estado === 'OBSERVADO';
                                                    $tituloRequisito = $requisitoCertificado->requisito?->descripcion ?? 'Requisito no encontrado';
                                                @endphp

                                                <section class="review-detail-panel {{ $filaObservada ? 'is-observed' : '' }}"
                                                    data-review-panel="{{ $requisitoCertificado->id }}"
                                                    @if (!$loop->first) hidden @endif>
                                                    <header class="review-detail-head">
                                                        <div>
                                                            <span class="review-detail-label"><i class="fa-regular fa-file-lines"></i> Requisito</span>
                                                            <h3>{{ $tituloRequisito }}</h3>
                                                        </div>
                                                        <div class="review-detail-head-actions">
                                                            <span class="review-evidence-chip">{{ $codigoEvidencia }}</span>
                                                            <span class="review-state is-{{ $decisionActual ? strtolower($decisionActual) : 'pending' }}" data-review-detail-state>
                                                                <i class="{{ $decisionActual === 'SI' ? 'fa-regular fa-circle-check' : ($decisionActual === 'NO' ? 'fa-regular fa-circle-xmark' : 'fa-solid fa-circle') }}" aria-hidden="true"></i>
                                                                <span>{{ $decisionActual === 'SI' ? 'Cumple' : ($decisionActual === 'NO' ? 'No cumple' : 'Pendiente') }}</span>
                                                            </span>
                                                        </div>
                                                    </header>

                                                    <div class="review-detail-content">
                                                        <div class="review-evidence-column">
                                                            <h4>Descripción de la evidencia</h4>
                                                            <p>{{ $descripcionEvidencia }}</p>

                                                            <div class="review-evidence-preview">
                                                                @if ($codigoEvidencia === 'PDF' && $documentoRequisito)
                                                                    <iframe src="{{ $documentoRequisito }}#toolbar=0&navpanes=0" title="Evidencia PDF: {{ $tituloRequisito }}" loading="lazy"></iframe>
                                                                    <div class="review-evidence-preview-actions">
                                                                        <span><i class="fa-regular fa-file-pdf"></i> Evidencia presentada</span>
                                                                        <a href="{{ $documentoRequisito }}" target="_blank" rel="noopener"><i class="fa-solid fa-up-right-from-square"></i> Ampliar</a>
                                                                    </div>
                                                                @elseif ($codigoEvidencia === 'IMAGEN' && $documentoRequisito)
                                                                    <img src="{{ $documentoRequisito }}" alt="Evidencia del requisito: {{ $tituloRequisito }}" loading="lazy">
                                                                    <div class="review-evidence-preview-actions">
                                                                        <span><i class="fa-regular fa-image"></i> Evidencia presentada</span>
                                                                        <a href="{{ $documentoRequisito }}" target="_blank" rel="noopener"><i class="fa-solid fa-up-right-from-square"></i> Ampliar</a>
                                                                    </div>
                                                                @elseif ($esFilaPago && $tienePagoRegistrado && $comprobantePagoPrincipal)
                                                                    <iframe src="{{ $comprobantePagoPrincipal }}#toolbar=0&navpanes=0"
                                                                        title="Comprobante de pago del trámite"
                                                                        loading="lazy"></iframe>
                                                                    <div class="review-evidence-preview-actions">
                                                                        <span><i class="fa-regular fa-file-pdf"></i> Comprobante registrado</span>
                                                                        <a href="{{ $comprobantePagoPrincipal }}" target="_blank" rel="noopener">
                                                                            <i class="fa-solid fa-up-right-from-square"></i> Ampliar
                                                                        </a>
                                                                    </div>
                                                                @elseif ($esFilaPago && $tienePagoRegistrado)
                                                                    <div class="review-evidence-reference">
                                                                        <i class="{{ $iconoEvidencia }}"></i>
                                                                        <span>Pago registrado sin comprobante adjunto.</span>
                                                                    </div>
                                                                @elseif ($esFilaPago && $puedeRegistrarPago)
                                                                    <button type="button" class="review-evidence-reference is-action" data-open-payment-modal>
                                                                        <i class="{{ $iconoEvidencia }}"></i>
                                                                        <span>Este requisito necesita un pago registrado.</span>
                                                                        <strong>Registrar pago</strong>
                                                                    </button>
                                                                @elseif ($codigoEvidencia === 'TEXTO' && filled($evidenciaPrincipal?->valor))
                                                                    <div class="review-evidence-text">{{ $evidenciaPrincipal->valor }}</div>
                                                                @elseif ($documentoRequisito)
                                                                    <a href="{{ $documentoRequisito }}" target="_blank" rel="noopener" class="review-evidence-reference is-action">
                                                                        <i class="{{ $iconoEvidencia }}"></i>
                                                                        <span>Evidencia registrada.</span>
                                                                        <strong>{{ $textoEvidenciaRequisito($codigoEvidencia, $filaObservada) }}</strong>
                                                                    </a>
                                                                @else
                                                                    <div class="review-evidence-reference is-empty">
                                                                        <i class="{{ $iconoEvidencia }}"></i>
                                                                        <span>Sin evidencia disponible.</span>
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </div>

                                                        <div class="review-decision-column">
                                                            <h4>¿Cumple el requisito?</h4>
                                                            <div class="review-decision-options">
                                                                <button type="button" class="is-yes {{ $decisionActual === 'SI' ? 'is-selected' : '' }}" value="SI" data-review-choice><i class="fa-regular fa-circle-check"></i> Sí</button>
                                                                <button type="button" class="is-no {{ $decisionActual === 'NO' ? 'is-selected' : '' }}" value="NO" data-review-choice><i class="fa-regular fa-circle-xmark"></i> No</button>
                                                            </div>

                                                            <p class="review-decision-help" data-review-decision-help>
                                                                {{ $decisionActual === 'NO' ? 'Indique qué debe corregir el solicitante.' : 'Seleccione el resultado de la revisión.' }}
                                                            </p>

                                                            <div class="review-observation-field" data-review-observation @if ($decisionActual !== 'NO') hidden @endif>
                                                                <label for="revision_observacion_{{ $requisitoCertificado->id }}">Motivo de incumplimiento <span>*</span></label>
                                                                <textarea id="revision_observacion_{{ $requisitoCertificado->id }}"
                                                                    name="requisitos_revision[{{ $loop->index }}][observacion]"
                                                                    maxlength="1000"
                                                                    placeholder="Explique qué debe corregir el solicitante."
                                                                    data-observation-input
                                                                    @if ($decisionActual !== 'NO') disabled @endif>{{ $observacionActual }}</textarea>
                                                                @if ($observacionDeOtro)
                                                                    <small>Al guardar se registrará una nueva observación.</small>
                                                                @endif
                                                            </div>
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
                                            <button type="button" class="tramite-btn tramite-btn-primary" disabled><i class="fa-regular fa-floppy-disk"></i> Guardar revisión</button>
                                        @else
                                            <button type="submit" class="tramite-btn tramite-btn-primary" data-review-save-next><i class="fa-regular fa-floppy-disk"></i> Guardar revisión</button>
                                        @endif

                                        @if ($certificado->estado !== 'OBSERVADO' && $puedeNotificarCorreccion)
                                            <button type="button" class="tramite-btn tramite-btn-notify" data-open-correction-recipient-modal>
                                                <i class="fa-solid fa-paper-plane"></i>
                                                Notificar solicitante
                                            </button>
                                        @endif
                                    </div>
                                @else
                                    <div class="review-workbench-empty">Este trámite no tiene requisitos registrados.</div>
                                @endif

                                @if ($certificado->estado === 'OBSERVADO' || $puedeFinalizarTramite)
                                    <div class="tramite-actions-row">
                                        @if ($certificado->estado === 'OBSERVADO')
                                            <button type="button" class="tramite-btn tramite-btn-muted" disabled>
                                                <i class="fa-solid fa-arrow-right"></i>
                                                Derivar
                                            </button>
                                            <span class="tramite-warning-box">
                                                <i class="fa-solid fa-triangle-exclamation"></i>
                                                Trámite observado: no se puede derivar ni continuar revisión hasta que el solicitante corrija.
                                            </span>
                                        @else
                                            @if ($puedeFinalizarTramite)
                                                <button type="{{ $tramiteRequiereHabilitarTramitador ? 'button' : 'submit' }}"
                                                    @unless ($tramiteRequiereHabilitarTramitador) form="form-finalizar-tramite" @endunless
                                                    class="tramite-btn tramite-btn-ok"
                                                    @if ($tramiteRequiereHabilitarTramitador) data-confirmar-tramitador @endif>
                                                    <i class="fa-solid fa-circle-check"></i>
                                                    Finalizar trámite
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                @endif
                            </form>
                            @if ($puedeNotificarCorreccion)
                                <form id="form-notificar-correccion-v2" action="{{ route('seguimientos_notificar_correccion', $seguimientoTecnicoActual) }}" method="POST" data-prevent-double-submit data-loading-button="Notificando...">
                                    @csrf
                                </form>

                                <div class="tramite-modal" data-correction-recipient-modal aria-hidden="true">
                                    <div class="tramite-modal-backdrop" data-close-correction-recipient-modal></div>
                                    <section class="tramite-modal-panel tramite-modal-panel-correction" role="dialog" aria-modal="true" aria-labelledby="tituloDestinoCorreccion">
                                        <div class="tramite-modal-head">
                                            <div>
                                                <h2 id="tituloDestinoCorreccion" class="tramite-card-title">
                                                    <i class="fa-solid fa-paper-plane"></i>
                                                    Notificar solicitante
                                                </h2>
                                                <p>Seleccione quién corregirá los requisitos observados.</p>
                                            </div>
                                            <button type="button" class="tramite-modal-close" data-close-correction-recipient-modal aria-label="Cerrar">
                                                <i class="fa-solid fa-xmark"></i>
                                            </button>
                                        </div>

                                        @php
                                            $destinatarioSeleccionado = $destinatariosCorreccion->firstWhere('id', $idDestinatarioCorreccion);
                                        @endphp
                                        <div class="cert-technical-field" data-correction-recipient-selector>
                                            <label class="cert-show-label" for="id_usuario_responsable_correccion">Responsable de la corrección</label>
                                            <input type="hidden" id="id_usuario_responsable_correccion" name="id_usuario_responsable_correccion"
                                                form="form-notificar-correccion-v2" value="{{ $idDestinatarioCorreccion }}" data-correction-recipient-value>
                                            <button type="button" class="cert-technical-control" data-correction-recipient-toggle
                                                @disabled($destinatarioCorreccionBloqueado) aria-expanded="false">
                                                <span class="cert-technical-avatar"><i class="fa-regular fa-user"></i></span>
                                                <span class="cert-technical-selected">
                                                    <span class="cert-technical-selected-name" data-correction-recipient-name>{{ $destinatarioSeleccionado['nombre'] ?? 'Seleccione una persona' }}</span>
                                                    <span class="cert-technical-selected-help" data-correction-recipient-type>{{ $destinatarioSeleccionado['tipo'] ?? '' }}</span>
                                                </span>
                                                <i class="fa-solid fa-chevron-down cert-technical-chevron"></i>
                                            </button>
                                            <div class="cert-technical-dropdown" data-correction-recipient-menu hidden>
                                            <div class="cert-technical-search">
                                                <i class="fa-solid fa-magnifying-glass"></i>
                                                <input type="search" data-correction-recipient-search placeholder="Buscar representante legal o tramitador">
                                            </div>
                                            <div class="cert-technical-options">
                                                @foreach ($destinatariosCorreccion as $destinatario)
                                                    <button type="button" class="cert-technical-option" data-correction-recipient-option
                                                        data-value="{{ $destinatario['id'] }}" data-nombre="{{ $destinatario['nombre'] }}"
                                                        data-tipo="{{ $destinatario['tipo'] }}" data-busqueda="{{ $destinatario['busqueda'] }}">
                                                        <span class="cert-technical-option-icon">
                                                            <i class="fa-solid {{ $destinatario['tipo'] === 'Representante legal' ? 'fa-user-tie' : 'fa-user-check' }}"></i>
                                                        </span>
                                                        <span class="cert-technical-option-main">
                                                            <strong>{{ $destinatario['nombre'] }}</strong>
                                                            <span>{{ $destinatario['tipo'] }}</span>
                                                        </span>
                                                        </button>
                                                    @endforeach
                                                    <div class="cert-technical-empty is-hidden" data-correction-recipient-empty>No se encontraron resultados.</div>
                                                </div>
                                            </div>
                                        </div>
                                        @if ($destinatarioCorreccionBloqueado)
                                            <p class="mt-2 text-sm text-slate-600">La persona natural es beneficiario y tramitador del mismo trámite.</p>
                                        @endif

                                        <div class="tramite-actions-row mt-5">
                                            <button type="button" class="tramite-btn tramite-btn-muted" data-close-correction-recipient-modal>Cancelar</button>
                                            <button type="submit" form="form-notificar-correccion-v2" class="tramite-btn tramite-btn-notify">
                                                <i class="fa-solid fa-paper-plane"></i>
                                                Notificar solicitante
                                            </button>
                                        </div>
                                    </section>
                                </div>
                            @endif
                            @if ($puedeFinalizarTramite)
                                <form id="form-finalizar-tramite" action="{{ route('seguimientos_finalizar_tramite', $seguimientoTecnicoActual) }}" method="POST">
                                    @csrf
                                    @if ($tramiteRequiereHabilitarTramitador)
                                        <input type="hidden" name="aceptar_tramitador" id="aceptar-tramitador" value="">
                                    @endif
                                </form>
                            @endif
                        @elseif ($seguimientoCorreccionActual)
                            {{-- El solicitante corrige cada requisito segun el tipo de evidencia configurado. --}}
                            <form id="form-correccion-requisitos-v2"
                                action="{{ route('seguimientos_reenviar_correccion', $seguimientoCorreccionActual) }}"
                                method="POST"
                                enctype="multipart/form-data"
                                data-prevent-double-submit
                                data-loading-button="Devolviendo...">
                                @csrf

                                <div class="cert-show-table-wrap tramite-table-wrap">
                                    <table class="cert-show-table cert-review-table tramite-table tramite-requirements-table cert-correction-table">
                                        <thead>
                                            <tr>
                                                <th class="cert-review-col-number">#</th>
                                                <th>Requisito</th>
                                                <th class="cert-review-col-result">Cumple</th>
                                                <th>Estado</th>
                                                <th>Evidencia actual</th>
                                                <th>Observación del técnico</th>
                                                <th>Corrección</th>
                                                <th class="cert-review-col-history">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse ($certificado->certificadoRequisitos->where('cumple', 'NO') as $requisitoCertificado)
                                                @php
                                                    $documentoRequisito = $urlDocumentoRequisito($requisitoCertificado);
                                                    $codigoEvidencia = $codigoEvidenciaRequisito($requisitoCertificado);
                                                    $iconoEvidencia = $iconoEvidenciaRequisito($codigoEvidencia);
                                                    $ultimaObservacionCorreccion = $ultimaObservacionDeRequisito($requisitoCertificado);
                                                    $evidenciaPrincipalCorreccion = $requisitoCertificado->evidenciasRequisitos->sortByDesc('id')->first();
                                                    $valorEvidenciaCorreccion = $evidenciaPrincipalCorreccion?->valor;
                                                    $rutaProductoCorreccion = route('productos_create', [
                                                        'form_id_certificado' => $certificado->id,
                                                        'form_id_importador_persona' => $certificado->id_persona_beneficiario,
                                                        'return_to' => request()->fullUrl(),
                                                    ]);
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
                                                        @if (in_array($codigoEvidencia, ['PDF', 'IMAGEN'], true))
                                                            <label class="cert-correction-file">
                                                                <input type="file"
                                                                    name="documentos_correccion[{{ $requisitoCertificado->id }}]"
                                                                    accept="{{ $codigoEvidencia === 'IMAGEN' ? 'image/jpeg,image/png,image/webp,.jpg,.jpeg,.png,.webp' : 'application/pdf,.pdf' }}"
                                                                    data-correction-file-input>
                                                                <span>
                                                                    <i class="fa-solid fa-upload"></i>
                                                                    {{ $codigoEvidencia === 'IMAGEN' ? 'Seleccionar imagen' : 'Seleccionar PDF' }}
                                                                </span>
                                                            </label>
                                                            <div class="cert-correction-file-preview" data-correction-file-preview hidden>
                                                                <span data-correction-file-name></span>
                                                                <a href="#" target="_blank" data-correction-file-link>Ver seleccionado</a>
                                                            </div>
                                                            @error("documentos_correccion.{$requisitoCertificado->id}")
                                                                <p class="cert-correction-error">{{ $message }}</p>
                                                            @enderror
                                                        @elseif ($codigoEvidencia === 'TEXTO')
                                                            <textarea
                                                                name="textos_correccion[{{ $requisitoCertificado->id }}]"
                                                                class="cert-correction-textarea"
                                                                rows="3"
                                                                placeholder="Escriba la corrección solicitada">{{ old("textos_correccion.{$requisitoCertificado->id}", $valorEvidenciaCorreccion) }}</textarea>
                                                            @error("textos_correccion.{$requisitoCertificado->id}")
                                                                <p class="cert-correction-error">{{ $message }}</p>
                                                            @enderror
                                                        @elseif ($codigoEvidencia === 'PRODUCTO')
                                                            <a href="{{ $rutaProductoCorreccion }}" class="cert-correction-action">
                                                                <i class="fa-solid fa-box"></i>
                                                                Registrar producto
                                                            </a>
                                                            @error("requisito_producto_{$requisitoCertificado->id}")
                                                                <p class="cert-correction-error">{{ $message }}</p>
                                                            @enderror
                                                        @elseif ($codigoEvidencia === 'PAGO')
                                                            <span class="cert-correction-note">
                                                                No requiere archivo en esta pantalla.
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
                                                    <td>
                                                        <button type="button" class="cert-history-button" data-requirement-history-button data-requirement-id="{{ $requisitoCertificado->id }}">
                                                            <i class="fa-regular fa-clock"></i>
                                                            Historial
                                                        </button>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8" class="text-center">Sin requisitos pendientes.</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>

                                <div class="tramite-actions-row mt-4">
                                    <button type="submit" name="accion_correccion" value="guardar" class="tramite-btn tramite-btn-secondary">
                                        <i class="fa-solid fa-floppy-disk"></i>
                                        Guardar corrección
                                    </button>
                                    <button type="submit" name="accion_correccion" value="enviar" class="tramite-btn tramite-btn-primary" onclick="return confirm('El trámite volverá al mismo revisor que envió la observación. ¿Desea continuar?')">
                                        <i class="fa-solid fa-reply"></i>
                                        Devolver al revisor
                                    </button>
                                </div>
                            </form>
                        @else
                            <div class="cert-show-table-wrap tramite-table-wrap">
                                <table class="cert-show-table cert-review-table tramite-table tramite-requirements-table is-readonly">
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
                                                $comprobantePagoPrincipal = $pagoPrincipalTramite?->comprobante
                                                    ? route('pagos_comprobante', $pagoPrincipalTramite)
                                                    : null;
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
                                                $claseEstadoActual = $claseEstadoRequisito($requisitoCertificado->estado);
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
                                                    <span class="tramite-pill tramite-status-chip {{ $claseEstadoActual }}">
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
                                                <td colspan="7" class="text-center">Este trámite no tiene requisitos registrados.</td>
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
            @endif

            @if ($requierePagoTramite && ($puedeRegistrarPago || $puedeEditarPago))
                {{-- El mismo formulario registra o corrige el pago sin duplicar campos ni reglas. --}}
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
                                    <span data-payment-modal-title>{{ $modalPagoEnEdicion ? 'Editar pago' : 'Registrar pago' }}</span>
                                </h2>
                                <p data-payment-modal-description>{{ $modalPagoEnEdicion ? 'Corrija los datos del pago relacionado a este trámite.' : 'Complete el pago relacionado a este trámite.' }}</p>
                            </div>
                            <button type="button" class="tramite-modal-close" data-close-payment-modal aria-label="Cerrar modal de pago">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>

                        <form action="{{ $modalPagoEnEdicion ? route('pagos_update', $pagoEditando) : route('pagos_store') }}"
                            method="POST"
                            enctype="multipart/form-data"
                            data-payment-form
                            data-store-url="{{ route('pagos_store') }}"
                            data-current-pdf-url="{{ $pagoEditando?->comprobante ? route('pagos_comprobante', $pagoEditando) : '' }}">
                            @csrf
                            <input type="hidden" data-payment-method @if ($modalPagoEnEdicion) name="_method" value="PUT" @endif>
                            <input type="hidden" name="form_id_pago" data-payment-id value="{{ $pagoEditando?->id }}">
                            <input type="hidden" name="form_id_certificado" value="{{ $certificado->id }}">
                            <input type="hidden" name="form_bandeja" value="{{ request('bandeja', 'recibidas') }}">

                            <div class="tramite-payment-form-grid">
                                <div class="tramite-payment-field-6">
                                    <label class="cert-show-label" for="form_id_procedencia_pago">Procedencia</label>
                                    <select id="form_id_procedencia_pago" name="form_id_procedencia_pago" class="cert-review-select @error('form_id_procedencia_pago') is-invalid @enderror" required>
                                        <option value="">Seleccione procedencia</option>
                                        @foreach ($procedenciasPago as $procedencia)
                                            <option value="{{ $procedencia->id }}" @selected(old('form_id_procedencia_pago', $pagoEditando?->id_procedencia) == $procedencia->id)>
                                                {{ $procedencia->codigo }}{{ $procedencia->descripcion ? ' - ' . $procedencia->descripcion : '' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('form_id_procedencia_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-6">
                                    <label class="cert-show-label" for="form_tipo_pago">Tipo de pago</label>
                                    <select id="form_tipo_pago" name="form_tipo_pago" class="cert-review-select @error('form_tipo_pago') is-invalid @enderror" required>
                                        <option value="">Seleccione tipo</option>
                                        @foreach (\App\Models\Pago::TIPOS_PAGOS as $valor => $texto)
                                            <option value="{{ $valor }}" @selected(old('form_tipo_pago', $pagoEditando?->tipo_pago) === $valor)>{{ $texto }}</option>
                                        @endforeach
                                    </select>
                                    @error('form_tipo_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-4">
                                    <label class="cert-show-label" for="form_fecha_pago">Fecha de pago</label>
                                    <input id="form_fecha_pago" name="form_fecha_pago" type="date" class="cert-review-select @error('form_fecha_pago') is-invalid @enderror" value="{{ old('form_fecha_pago', $pagoEditando?->fecha ?? now()->toDateString()) }}" required>
                                    @error('form_fecha_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-4">
                                    <label class="cert-show-label" for="form_monto_pago">Monto</label>
                                    <input id="form_monto_pago" name="form_monto_pago" type="number" min="0.01" step="0.01" class="cert-review-select @error('form_monto_pago') is-invalid @enderror" value="{{ old('form_monto_pago', $pagoEditando?->monto) }}" placeholder="0.00" required>
                                    @error('form_monto_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-4">
                                    <label class="cert-show-label" for="form_factura_pago">Factura</label>
                                    <input id="form_factura_pago" name="form_factura_pago" type="text" maxlength="100" class="cert-review-select @error('form_factura_pago') is-invalid @enderror" value="{{ old('form_factura_pago', $pagoEditando?->factura) }}" placeholder="Ingrese el número de factura">
                                    @error('form_factura_pago')
                                        <p class="text-xs font-semibold text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div class="tramite-payment-field-12">
                                    <label class="cert-show-label" for="form_comprobante_pago">Comprobante PDF</label>
                                    <div class="tramite-payment-pdf @error('form_comprobante_pago') is-invalid @enderror">
                                        <input id="form_comprobante_pago" name="form_comprobante_pago" type="file" accept="application/pdf,.pdf" class="hidden" data-payment-pdf-input>
                                        <span class="tramite-payment-pdf-icon"><i class="fa-regular fa-file-pdf"></i></span>
                                        <span class="tramite-payment-pdf-details">
                                            <strong class="tramite-payment-pdf-name" data-payment-pdf-name>{{ $modalPagoEnEdicion && $pagoEditando?->comprobante ? 'Comprobante actual registrado' : 'Sin PDF seleccionado' }}</strong>
                                            <small data-payment-pdf-help>{{ $modalPagoEnEdicion ? 'Seleccione otro PDF solo para reemplazarlo' : 'PDF opcional · Máximo 10 MB' }}</small>
                                        </span>
                                        <span class="tramite-payment-pdf-actions">
                                            <button type="button" class="tramite-payment-pdf-button is-select" data-payment-pdf-select aria-label="Seleccionar comprobante PDF" title="Seleccionar PDF">
                                                <i class="fa-solid fa-upload" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="tramite-payment-pdf-button is-view" data-payment-pdf-view aria-label="Ver comprobante PDF" title="Ver PDF" disabled>
                                                <i class="fa-regular fa-eye" aria-hidden="true"></i>
                                            </button>
                                            <button type="button" class="tramite-payment-pdf-button is-remove" data-payment-pdf-remove aria-label="Quitar comprobante PDF" title="Quitar PDF" disabled>
                                                <i class="fa-solid fa-trash-can" aria-hidden="true"></i>
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
                                    <span data-payment-submit-label>{{ $modalPagoEnEdicion ? 'Actualizar pago' : 'Registrar pago' }}</span>
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
                                    <dd>
                                        <span class="block">{{ $nombrePersona($certificado->tramitador) }}</span>
                                        <span class="tramite-summary-role">{{ $tipoRelacionTramitadorActual }}</span>
                                    </dd>
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
                                    <dt>Registrado inicialmente por</dt>
                                    <dd class="tramite-user-stack">
                                        <span class="tramite-user-name">{{ $nombreUsuario($seguimientoOrigenDetalle?->usuarioOrigen, 'Sin usuario de registro') }}</span>
                                        @if ($seguimientoOrigenDetalle?->usuarioOrigen)
                                            <span class="tramite-user-cargo">
                                                {{ $rolUsuarioEnTramite($seguimientoOrigenDetalle->usuarioOrigen) }}
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
                            <div class="tramite-payment-records">
                                @forelse ($certificado->pagos as $pago)
                                    <article class="tramite-payment-record">
                                        <header class="tramite-payment-record-head">
                                            <div class="tramite-payment-record-heading">
                                                <div>
                                                    <strong>Pago registrado</strong>
                                                    <span>{{ $textoTipoPago($pago->tipo_pago) }}</span>
                                                </div>
                                            </div>
                                            <div class="tramite-payment-record-amount">
                                                <small>Monto</small>
                                                <strong>{{ number_format((float) $pago->monto, 2, ',', '.') }} Bs.</strong>
                                            </div>
                                        </header>

                                        @if ($puedeEditarPago)
                                            <div class="tramite-payment-record-actions">
                                                <button type="button"
                                                    class="tramite-btn tramite-btn-secondary"
                                                    data-edit-payment
                                                    data-update-url="{{ route('pagos_update', $pago) }}"
                                                    data-payment-id="{{ $pago->id }}"
                                                    data-procedencia="{{ $pago->id_procedencia }}"
                                                    data-tipo="{{ $pago->tipo_pago }}"
                                                    data-fecha="{{ $pago->fecha ? \Illuminate\Support\Carbon::parse($pago->fecha)->format('Y-m-d') : '' }}"
                                                    data-monto="{{ $pago->monto }}"
                                                    data-factura="{{ $pago->factura }}"
                                                    data-comprobante="{{ $pago->comprobante ? route('pagos_comprobante', $pago) : '' }}">
                                                    <i class="fa-solid fa-pen-to-square"></i>
                                                    Editar pago
                                                </button>
                                            </div>
                                        @endif

                                        <dl class="tramite-payment-record-grid">
                                            <div>
                                                <dt>Procedencia</dt>
                                                <dd>{{ $pago->procedencia?->descripcion ?? 'Sin procedencia' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Fecha de pago</dt>
                                                <dd>{{ $fechaCorta($pago->fecha) }}</dd>
                                            </div>
                                            <div>
                                                <dt>Factura</dt>
                                                <dd>{{ $pago->factura ?: 'Sin factura' }}</dd>
                                            </div>
                                            <div>
                                                <dt>Fecha de validación</dt>
                                                <dd>{{ $fechaCorta($pago->fecha_validacion) }}</dd>
                                            </div>
                                            <div class="is-wide">
                                                <dt>Cliente</dt>
                                                <dd>{{ $nombrePersona($pago->clientePersona) }}</dd>
                                            </div>
                                            <div class="is-wide">
                                                <dt>Registrado por</dt>
                                                <dd class="tramite-user-stack">
                                                    <span class="tramite-user-name">{{ $nombreUsuario($pago->funcionarioUsuario, 'Sin funcionario') }}</span>
                                                    <span class="tramite-user-cargo">{{ $cargoUsuario($pago->funcionarioUsuario) }}</span>
                                                </dd>
                                            </div>
                                            <div class="is-full">
                                                <dt>Comprobante</dt>
                                                <dd>
                                                    @php
                                                        $rutaComprobantePago = preg_replace('#^/?storage/#', '', (string) $pago->comprobante);
                                                        $comprobantePagoDisponible = filled($rutaComprobantePago)
                                                            && \Illuminate\Support\Facades\Storage::disk('public')->exists($rutaComprobantePago);
                                                    @endphp
                                                    @if ($comprobantePagoDisponible)
                                                        <a href="{{ route('pagos_comprobante', $pago) }}" target="_blank" class="tramite-doc-link tramite-payment-document-link">
                                                            <i class="fa-regular fa-file-pdf"></i>
                                                            Ver comprobante PDF
                                                        </a>
                                                    @elseif ($pago->comprobante)
                                                        <span class="tramite-pill tramite-pill-danger">Archivo no disponible</span>
                                                    @else
                                                        <span class="tramite-pill tramite-pill-warn">Sin PDF</span>
                                                    @endif
                                                </dd>
                                            </div>
                                        </dl>
                                    </article>
                                @empty
                                    <div class="tramite-payment-empty">Este trámite no tiene pago registrado.</div>
                                @endforelse
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

    @if ($puedeFinalizarTramite && $tramiteRequiereHabilitarTramitador)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const boton = document.querySelector('[data-confirmar-tramitador]');
                const formulario = document.getElementById('form-finalizar-tramite');
                const confirmacion = document.getElementById('aceptar-tramitador');

                if (!boton || !formulario || !confirmacion) {
                    return;
                }

                boton.addEventListener('click', async () => {
                    const resultado = await Swal.fire({
                        title: 'Aceptar tramitador',
                        text: @json('Se habilitará a ' . $nombrePersona($certificado->tramitador) . ' como tramitador de ' . $nombrePersona($certificado->beneficiario) . '.'),
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Aceptar y finalizar',
                        cancelButtonText: 'Cancelar',
                    });

                    if (!resultado.isConfirmed) {
                        return;
                    }

                    confirmacion.value = '1';
                    formulario.submit();
                });
            });
        </script>
    @endif

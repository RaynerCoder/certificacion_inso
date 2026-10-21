{{--
    Vista principal del detalle del trámite.
    Aquí se preparan los datos y se incluyen las diferentes secciones.
--}}
@php

    // -----------------------------------------------------------------------------
    // PERSONAS, EMPRESAS Y USUARIOS
    // -----------------------------------------------------------------------------
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

    // Devuelve si corresponde a una empresa o a una persona natural.
    $tipoPersona = function ($persona) {
        return $persona?->empresa ? 'Empresa' : 'Persona natural';
    };

    // Determina si actúa como representante legal o tramitador.
    $tipoRelacionTramitador = function ($personaTramitador, $personaBeneficiaria) {
        $empresa = $personaBeneficiaria?->empresa;
        if (! $empresa) {
            return (int) $personaTramitador?->id === (int) $personaBeneficiaria?->id
                ? 'Persona natural'
                : 'Tramitador de persona natural';
        }

        if (! $personaTramitador) {
            return 'Relación no identificada';
        }

        $relaciones = \App\Models\Responsable::withTrashed()
            ->with('rol:id,slug')
            ->where('id_empresa', $empresa->id)
            ->where('id_persona', $personaTramitador->id)
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
        
        return match (true) {
            $esRepresentante => 'Representante legal',
            $esTramitador => 'Tramitador',
            $rolesAnteriores->contains('tramitador') => 'Ex-Tramitador',
            $rolesAnteriores->contains('solicitante') => 'Ex-representante legal',
            default => 'Relación no identificada',
        };
    };

    $tipoRelacionTramitadorActual = $tipoRelacionTramitador(
        $certificado->tramitador,
        $certificado->beneficiario
    );
    $gestorActualDetalle = app(\App\Services\GestionTramitadoresService::class)->gestorActual($certificado);
    $personaGestoraActual = $gestorActualDetalle['persona'];
    $tipoGestorActual = $gestorActualDetalle['tipo'];

    // Devuelve CI para persona natural y NIT para empresa.
    $identificacionPersona = function ($persona) {
        if (!$persona) {
            return 'Sin dato';
        }

        if ($persona->empresa) {
            return $persona->nit ?: 'Sin NIT';
        }

        return $persona->natural?->ci ?: ($persona->nit ?: 'Sin CI/NIT');
    };

    // Usa el primer teléfono registrado.
    $telefonoPersona = function ($persona) {
        return $persona?->telefonos?->first()?->numero ?? 'Sin teléfono';
    };

    // Obtiene el nombre completo del usuario.
    $nombreUsuario = function ($usuario, string $fallback = 'Sin usuario') {
        if (!$usuario) {
            return $fallback;
        }

        $nombreCompleto = trim($usuario->nombreCompleto());

        return $nombreCompleto !== '' ? $nombreCompleto : $fallback;

    };

    // Obtiene los cargos registrados del funcionario.
    $cargoUsuario = function ($usuario, string $fallback = 'Sin cargo') {
        if (!$usuario) {
            return $fallback;
        }

        $usuario->loadMissing('funcionario.cargos');
        $cargos = $usuario->funcionario?->cargos?->pluck('nombre')->filter()->implode(', ');

        return $cargos ?: $fallback;

    };

    // Distingue entre funcionario y persona externa vinculada al trámite.
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


    
    // -----------------------------------------------------------------------------
    // ARCHIVOS, FECHAS Y TEXTOS
    // -----------------------------------------------------------------------------

    // Convierte una ruta guardada en una URL utilizable.
    $urlArchivo = function (?string $ruta) {
        if (!$ruta) {
            return null;
        }

        if (\Illuminate\Support\Str::startsWith($ruta, ['http://', 'https://'])) {
            return $ruta;
        }

        return \Illuminate\Support\Str::startsWith($ruta, 'storage/') ? asset($ruta) : asset('storage/' . $ruta);
    };

    // Formatea fechas para mostrarlas en la vista.
    $fechaCorta = function ($fecha, string $fallback = 'Sin fecha') {
        return $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y') : $fallback;
    };

    $fechaHora = function ($fecha, string $fallback = 'Sin fecha') {
        return $fecha ? \Illuminate\Support\Carbon::parse($fecha)->format('d/m/Y H:i') : $fallback;
    };

    // Convierte el tipo de pago en texto legible.
    $textoTipoPago = function (?string $tipoPago) {
        return \App\Models\Pago::TIPOS_PAGOS[$tipoPago] ?? ($tipoPago ?: 'Sin tipo');
    };



    // -----------------------------------------------------------------------------
    // ESTADOS Y RESUMEN DEL TRÁMITE
    // -----------------------------------------------------------------------------

    // Convierte el estado técnico en texto legible.
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

    // Calcula el avance general de los requisitos.
    $totalRequisitos = $certificado->certificadoRequisitos->count();
    $requisitosCumplidos = $certificado->certificadoRequisitos->where('cumple', 'SI')->count();
    $requisitosPendientes = $certificado->certificadoRequisitos->where('estado', 'PENDIENTE_REVISION')->count();
    $porcentajeRequisitos = $totalRequisitos > 0 ? round(($requisitosCumplidos / $totalRequisitos) * 100) : 0;

    // Define la clase visual según el estado del certificado.
    $estadoClaseShow = match ($certificado->estado) {
        'APROBADO', 'EMITIDO' => 'cert-show-badge-ok',
        'VENCIDO', 'ANULADO', 'RECHAZADO' => 'cert-show-badge-danger',
        'OBSERVADO' => 'cert-show-badge-warning',
        'EN_REVISION' => 'cert-show-badge-info',
        default => 'cert-show-badge-neutral',
    };

    $estadoTextoShow = \App\Models\Certificado::textoEstadoCertificado($certificado->estado);
    $estadoIconoShow = \App\Models\Certificado::iconoEstadoCertificado($certificado->estado);



    // -----------------------------------------------------------------------------
    // EVIDENCIAS Y REQUISITOS
    // -----------------------------------------------------------------------------

    // Obtiene la evidencia de archivo más reciente.
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

    $codigoEvidenciaRequisito = function ($requisitoCertificado) use ($evidenciaPrincipalRequisito, $configuracionesRequisitos) {

        $codigo = $evidenciaPrincipalRequisito($requisitoCertificado)?->tipoEvidencia?->codigo
            ?? $configuracionesRequisitos->get($requisitoCertificado->id_requisito)?->tipoEvidencia?->codigo;

        return strtoupper((string) ($codigo ?? 'SIN_EVIDENCIA'));

    };

    // Obtiene el nombre del tipo de evidencia.
    $nombreEvidenciaRequisito = function ($requisitoCertificado) use ($evidenciaPrincipalRequisito, $configuracionesRequisitos) {
        
        $tipoEvidencia = $evidenciaPrincipalRequisito($requisitoCertificado)?->tipoEvidencia
            ?? $configuracionesRequisitos->get($requisitoCertificado->id_requisito)?->tipoEvidencia;

        return $tipoEvidencia?->nombre ?: ($tipoEvidencia?->codigo ?: 'Sin tipo definido');
    };

    $descripcionEvidenciaRequisito = function ($requisitoCertificado) use ($evidenciaPrincipalRequisito, $configuracionesRequisitos) {
        
        return ($evidenciaPrincipalRequisito($requisitoCertificado)?->tipoEvidencia
            ?? $configuracionesRequisitos->get($requisitoCertificado->id_requisito)?->tipoEvidencia)?->descripcion
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
            'CALIFICACION' => 'fa-solid fa-graduation-cap',
            default => 'fa-regular fa-file',
        };
    };

    // Verifica si el requisito tiene alguno de los tipos de evidencia indicados.
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

    // Obtiene la última revisión técnica.
    $ultimaRevisionRequisito = function ($requisitoCertificado) {
        return $requisitoCertificado->revisionesRequisitos
            ->sortByDesc('id')
            ->first();
    };

    // Reúne las observaciones registradas en las revisiones.
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

    // Obtiene la última observación registrada.
    $ultimaObservacionDeRequisito = function ($requisitoCertificado) use ($observacionesDeRequisito) {
        return $observacionesDeRequisito($requisitoCertificado)->sortByDesc('id')->first();
    };

    // Obtiene una URL autorizada para abrir el documento.
    $urlDocumentoRequisito = function ($requisitoCertificado) {
        $evidencia = $requisitoCertificado->evidenciasRequisitos
            ->filter(fn ($item) => in_array($item->tipoEvidencia?->codigo, ['PDF', 'IMAGEN'], true))
            ->sortByDesc('id')
            ->first();

        if ($evidencia?->valor) {
            if (\Illuminate\Support\Str::startsWith($evidencia->valor, ['http://', 'https://'])) {
                return $evidencia->valor;
            }

            return route('certificados_evidencias_descargar', [
                'certificado' => $requisitoCertificado->id_certificado,
                'evidenciaRequisito' => $evidencia->id,
            ]);

        }
        $rutaPublica = 'storage/documentos/requisitos_certificados/' . $requisitoCertificado->id . '/documento.pdf';
        return file_exists(public_path($rutaPublica)) ? asset($rutaPublica) : null;
    };



    // -----------------------------------------------------------------------------
    // PRODUCTOS Y REGISTROS DEL TRÁMITE
    // -----------------------------------------------------------------------------

    // Agrupa registros del mismo producto para evitar duplicados.
    $registrosPorProducto = $certificado->registros->groupBy(
        fn ($registro) => $registro->producto?->id ? 'producto_' . $registro->producto->id : 'registro_' . $registro->id,
    );

    $tieneProductoRegistrado = $certificado->registros
        ->contains(fn ($registro) => filled($registro->producto?->id));



    // -----------------------------------------------------------------------------
    // HISTORIAL DE REVISIONES Y OBSERVACIONES
    // -----------------------------------------------------------------------------

    // Oculta el historial técnico mientras exista una corrección pendiente de notificar.
    $historialRequisitos = $mostrarRevisionRequisitos && ! $seguimientoCorreccionActual
        ? $certificado->certificadoRequisitos->mapWithKeys(function ($requisitoCertificado) use ($observacionesDeRequisito, $ultimaRevisionRequisito, $nombreUsuario, $cargoUsuario, $configuracionesRequisitos) {
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
        $configuracion = $configuracionesRequisitos->get($requisitoCertificado->id_requisito);

        if ($ultimaRevision?->calificacion_obtenida !== null) {
            $calificacion = (float) $ultimaRevision->calificacion_obtenida;
            $calificacionMinima = (float) $configuracion?->calificacion_minima_aprobacion;
            $resultadoCalificacion = $calificacion >= $calificacionMinima ? 'Aprobado' : 'Participación';
            $items->push([
                'tipo' => 'Calificación registrada',
                'estado' => 'success',
                'fecha' => $ultimaRevision->created_at?->format('d/m/Y H:i') ?? 'Sin fecha',
                'usuario' => $nombreUsuario($ultimaRevision->usuarioRevisor, 'Sin revisor'),
                'cargo' => $cargoUsuario($ultimaRevision->usuarioRevisor),
                'texto' => 'Calificación: '.number_format($calificacion, 2, ',', '.').' · Mínima: '.number_format($calificacionMinima, 2, ',', '.').' · '.$resultadoCalificacion,
            ]);
        }

        if ($requisitoCertificado->cumple === 'SI' && $ultimaRevision?->calificacion_obtenida === null) {
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
    }) : collect();



    // -----------------------------------------------------------------------------
    // SEGUIMIENTO Y RESPONSABLE ACTUAL
    // -----------------------------------------------------------------------------

    // Pasos visuales del seguimiento.
    $pasosSeguimiento = [
        ['nombre' => 'Solicitud', 'activo' => true],
        ['nombre' => 'Documentos', 'activo' => $totalRequisitos > 0],
        ['nombre' => 'Revisión técnica', 'activo' => in_array($certificado->estado, ['EN_REVISION', 'OBSERVADO', 'APROBADO', 'EMITIDO'], true)],
        ['nombre' => 'Seguimiento', 'activo' => true],
    ];

    // Obtiene el responsable del último seguimiento activo.
    $seguimientoActualDetalle = $certificado->seguimientos->where('estado', 'ACTIVO')->sortByDesc('id')->first()
        ?? $certificado->seguimientos->sortByDesc('id')->first();

    // Obtiene el primer seguimiento del trámite.
    $seguimientoOrigenDetalle = $certificado->seguimientos->sortBy('id')->first();
    $responsableActualDetalle = $nombreUsuario(
        $seguimientoActualDetalle?->usuarioSiguiente,
        $esSolicitante ? 'Solicitante' : 'Sin responsable'
    );

    $responsableActualCargoDetalle = $seguimientoActualDetalle?->usuarioSiguiente
        ? $cargoUsuario($seguimientoActualDetalle->usuarioSiguiente)
        : '';



    // -----------------------------------------------------------------------------
    // REQUISITOS ESPECIALES: PRODUCTO Y PAGO
    // -----------------------------------------------------------------------------

    // Verifica si el trámite requiere producto o pago.
    $requiereProductoTramite = $certificado->requiereEvidencia('PRODUCTO');
    $requierePagoTramite = $certificado->requiereEvidencia('PAGO');



    // -----------------------------------------------------------------------------
    // PAGO RELACIONADO CON EL TRÁMITE
    // -----------------------------------------------------------------------------

    // Obtiene el pago asociado al trámite.
    $pagoPrincipalTramite = $certificado->pagos->first();

    // Indica si ya existe un pago registrado.
    $tienePagoRegistrado = filled($pagoPrincipalTramite?->id);

    // -----------------------------------------------------------------------------
    // REGISTRO MANUAL DEL PAGO PARA FUNCIONARIOS
    // -----------------------------------------------------------------------------

    // Puede registrar si el trámite requiere pago, aún no existe uno
    // y el usuario tiene los permisos correspondientes.
    $puedeRegistrarPago = $requierePagoTramite
        && !$tienePagoRegistrado
        && !$esSolicitante
        && (auth()->user()?->puede('pagos.validar') ?? false)
        && ($puedeAsignarTecnico || $puedeRevisarRequisitos);

    // Puede editar un pago existente si tiene permiso para validarlo.
    $puedeEditarPago = $tienePagoRegistrado
        && !$esSolicitante
        && (auth()->user()?->puede('pagos.validar') ?? false);

    $idPagoSolicitado = old('form_id_pago', request('editar_pago'));
    $pagoEditando = $puedeEditarPago && (int) $idPagoSolicitado === (int) $pagoPrincipalTramite?->id
        ? $pagoPrincipalTramite
        : null;

    $modalPagoEnEdicion = filled($pagoEditando?->id);

    // Reabre el modal cuando la validación del formulario devuelve errores.
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




    // -----------------------------------------------------------------------------
    // ACCIONES PARA REGISTRAR PRODUCTOS
    // -----------------------------------------------------------------------------

    // Verifica si el funcionario puede registrar productos.

    // Habilita el registro de productos solo cuando corresponde al trámite
    // y el usuario tiene permisos para hacerlo.
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
        ]) : null;

@endphp

@include('certificados.partials.show_estilos')

<div class="tramite-detail-v2" data-tramite-detail-active>
        <div class="tramite-shell">

        {{-- Encabezado, resumen y descarga del certificado. --}}
        @include('certificados.detalle_tramite.encabezado_resumen')

        {{-- Revisión, historial y correcciones de los requisitos. --}}
        @include('certificados.detalle_tramite.revision_requisitos')

        {{-- Registro y edición manual de pagos para funcionarios. --}}
        @include('certificados.detalle_tramite.modal_pago_manual')

        {{-- Asignación y derivación del trámite al personal técnico. --}}
        @include('certificados.detalle_tramite.asignacion_tecnica')

            {{-- SECCION 1: datos base del tramite. Se separa solicitante, tramitador e inicio para no mezclar responsabilidades. --}}
            <section class="tramite-card tramite-section-detail">
                <div class="tramite-card-head">
                    <h2 class="tramite-card-title">Informacion principal</h2>
                </div>
                <div class="tramite-card-body">
                    <div class="tramite-detail-grid">
                        {{-- Beneficiario, tramitador e información de inicio. --}}
                        @include('certificados.detalle_tramite.datos_principales')

                        {{-- Productos y registros técnicos relacionados con el trámite. --}}
                        @include('certificados.detalle_tramite.productos')                       

                        {{-- Pagos y comprobantes visibles para personal interno. --}}
                        @include('certificados.detalle_tramite.pagos_registrados')
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
                    formulario.requestSubmit();
                });
            });
        </script>
    @endif

{{-- Acciones de tramites finalizados: se consulta el cierre y se imprime el certificado. --}}
<div class="flex flex-wrap items-center gap-1.5 text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <x-wire-button href="{{ route('seguimientos_show', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" emerald xs>
        Ver tramite
    </x-wire-button>

    <x-wire-button href="{{ route('seguimientos_tramite_historial', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" blue xs>
        Seguimiento
    </x-wire-button>

    @php
        $certificadoFinalizado = $seguimiento->certificado;
        $esEmisionPorLote = $certificadoFinalizado?->tipoCertificado?->modo_emision === 'POR_LOTE';
        $emisionesVigentes = $certificadoFinalizado?->emisiones
            ?->filter(fn ($emision) => in_array($emision->estado, ['EMITIDO', 'VENCIDO'], true) && filled($emision->url_documento))
            ->sortByDesc('orden_emision')
            ->values() ?? collect();
        $emisionVigente = $emisionesVigentes->first();
        $esDocumentoConsultable = $emisionesVigentes->isNotEmpty();
        $puedeAbrirEmision = $certificadoFinalizado?->puedeEmitirse() || $esDocumentoConsultable;
        $reglaVigencia = $certificadoFinalizado?->tipoCertificado?->datosVigencia() ?? ['cantidad' => null, 'unidad' => null];
        $vigenciaConfigurada = $reglaVigencia['unidad'] === 'INDEFINIDA'
            || (filled($reglaVigencia['cantidad']) && filled($reglaVigencia['unidad']));
        $puedeGestionarEmision = auth()->user()?->puede('certificados.emitir') ?? false;
        $anulacionDisponible = \Illuminate\Support\Facades\Schema::hasColumns('certificados_emitidos', [
            'fecha_anulacion',
            'id_usuario_anulacion',
            'motivo_anulacion',
        ]);
        $idModalCertificado = 'modal-certificado-' . $seguimiento->id;
        $idModalValidacion = 'modal-validacion-emision-' . $seguimiento->id;
        $faltantesEmision = collect();

        if (! $esDocumentoConsultable) {
            if (! $certificadoFinalizado?->tipoCertificado?->plantillaActiva) {
                $faltantesEmision->push('No existe una plantilla activa para este tipo de certificado.');
            }

            if (! $vigenciaConfigurada) {
                $faltantesEmision->push('Configure la vigencia del tipo de certificado para calcular la fecha final.');
            }

            if ($esEmisionPorLote && $certificadoFinalizado?->certificadosRegistros
                ?->filter(fn ($fila) => $fila->registro)
                ->isEmpty()) {
                $faltantesEmision->push('No hay registros asociados al trámite para emitir por lote.');
            }

            if (! $certificadoFinalizado?->cumpleTodosLosRequisitos()) {
                $faltantesEmision->push('Aún existen requisitos pendientes de cumplimiento.');
            }

            if (in_array($certificadoFinalizado?->estado, ['ANULADO', 'RECHAZADO'], true)) {
                $faltantesEmision->push('El trámite está ' . strtolower($certificadoFinalizado->estado) . ' y no puede emitirse.');
            } elseif (! in_array($certificadoFinalizado?->estado, ['APROBADO', 'EMITIDO'], true)) {
                $faltantesEmision->push('El trámite debe estar aprobado o finalizado antes de emitir.');
            }

            if (! $certificadoFinalizado?->puedeEmitirse() && $faltantesEmision->isEmpty()) {
                $faltantesEmision->push('El trámite no cumple todavía las condiciones necesarias para emitir.');
            }
        }
    @endphp

    @if ($puedeGestionarEmision)
        @if ($esDocumentoConsultable)
            <button type="button"
                class="inline-flex items-center rounded-md bg-amber-500 px-2 py-1 text-[11px] font-semibold text-white shadow-sm transition hover:bg-amber-600"
                onclick="window.abrirModalCertificado('{{ $idModalCertificado }}', 0)">
                <i class="fa-solid fa-file-pdf mr-1"></i>
                {{ $esEmisionPorLote ? 'Ver certificados' : 'Ver certificado' }}
            </button>
        @elseif ($faltantesEmision->isEmpty() && $puedeAbrirEmision)
            <x-wire-button href="{{ route('certificados_emitir', ['certificado' => $seguimiento->certificado, 'bandeja' => 'finalizados']) }}" amber xs>
                Emitir certificado
            </x-wire-button>
        @else
            <button type="button"
                class="inline-flex items-center rounded-md bg-amber-500 px-2 py-1 text-[11px] font-semibold text-white shadow-sm transition hover:bg-amber-600"
                onclick="window.abrirModalValidacionEmision('{{ $idModalValidacion }}')">
                <i class="fa-solid fa-file-circle-check mr-1"></i>
                Emitir certificado
            </button>
        @endif
    @endif

    @if ($esDocumentoConsultable)
        <div id="{{ $idModalCertificado }}" class="certificado-modal" hidden role="dialog" aria-modal="true"
            aria-labelledby="{{ $idModalCertificado }}-titulo" onclick="window.cerrarModalCertificado('{{ $idModalCertificado }}', event)">
            <section class="certificado-modal__card" onclick="event.stopPropagation()">
                <header class="certificado-modal__header">
                    <div>
                        <h2 id="{{ $idModalCertificado }}-titulo" class="certificado-modal__title">
                            {{ $esEmisionPorLote ? 'Certificados emitidos' : 'Certificado emitido' }}
                        </h2>
                        <p class="certificado-modal__subtitle">
                            {{ $certificadoFinalizado?->tipoCertificado?->nombre }}
                        </p>
                    </div>
                    <button type="button" class="certificado-modal__close" aria-label="Cerrar"
                        onclick="window.cerrarModalCertificado('{{ $idModalCertificado }}')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>

                <div class="certificado-modal__content" data-documento-preview>
                    @if ($emisionesVigentes->count() > 1)
                        <aside class="certificado-modal__documents" aria-label="Certificados del trámite">
                            <span class="certificado-modal__documents-title">Documentos emitidos</span>
                            @foreach ($emisionesVigentes as $indiceEmision => $emision)
                                <button type="button" class="certificado-modal__document {{ $indiceEmision === 0 ? 'is-active' : '' }}"
                                    data-documento-selector data-documento-index="{{ $indiceEmision }}"
                                    data-visualizar-url="{{ route('certificados_emitidos_descargar', ['certificado' => $certificadoFinalizado, 'emision' => $emision, 'visualizar' => 1]) }}"
                                    data-descargar-url="{{ route('certificados_emitidos_descargar', ['certificado' => $certificadoFinalizado, 'emision' => $emision]) }}"
                                    data-anular-url="{{ route('certificados_emitidos_anular', ['certificado' => $certificadoFinalizado, 'emision' => $emision]) }}"
                                    data-emision-id="{{ $emision->id }}" data-numero="{{ $emision->numero_certificado }}"
                                    onclick="window.seleccionarDocumentoCertificado('{{ $idModalCertificado }}', this)">
                                    <strong>{{ $emision->numero_certificado }}</strong>
                                    <small>{{ optional($emision->fecha_emision)->format('d/m/Y') }}</small>
                                </button>
                            @endforeach
                        </aside>
                    @endif

                    <div class="certificado-modal__viewer">
                        <iframe data-documento-frame
                            src="{{ route('certificados_emitidos_descargar', ['certificado' => $certificadoFinalizado, 'emision' => $emisionVigente, 'visualizar' => 1]) }}"
                            title="PDF del certificado {{ $emisionVigente->numero_certificado }}"></iframe>
                    </div>
                </div>

                <form class="certificado-modal__anulacion" data-panel-anulacion hidden method="POST"
                    action="{{ route('certificados_emitidos_anular', ['certificado' => $certificadoFinalizado, 'emision' => $emisionVigente]) }}">
                    @csrf
                    <input type="hidden" name="bandeja" value="finalizados">
                    <div class="certificado-modal__warning">
                        <i class="fa-solid fa-triangle-exclamation"></i>
                        <span>Se anulará el certificado <strong>N.º <span data-numero-anulacion>{{ $emisionVigente->numero_certificado }}</span></strong>.</span>
                    </div>
                    <label for="{{ $idModalCertificado }}-motivo" class="certificado-modal__label">
                        Motivo de anulación
                    </label>
                    <textarea id="{{ $idModalCertificado }}-motivo" name="form_motivo_anulacion" rows="4" maxlength="1000" required
                        placeholder="Describa el motivo de la anulación."></textarea>
                    <div class="certificado-modal__anulacion-actions">
                        <button type="button" class="certificado-modal__button certificado-modal__button--secondary"
                            onclick="window.mostrarPdfCertificado('{{ $idModalCertificado }}')">Volver al PDF</button>
                        <button type="submit" class="certificado-modal__button certificado-modal__button--danger">
                            <i class="fa-solid fa-ban"></i>
                            Confirmar anulación
                        </button>
                    </div>
                </form>

                <footer class="certificado-modal__footer" data-documento-actions>
                    <a data-documento-descargar class="certificado-modal__button certificado-modal__button--secondary"
                        href="{{ route('certificados_emitidos_descargar', ['certificado' => $certificadoFinalizado, 'emision' => $emisionVigente]) }}">
                        <i class="fa-solid fa-download"></i>
                        Descargar PDF
                    </a>

                    @if ($puedeGestionarEmision)
                        <form action="{{ route('certificados_enviar_solicitante', $certificadoFinalizado) }}" method="POST">
                            @csrf
                            <input data-documento-emision-id type="hidden" name="form_id_certificado_emitido" value="{{ $emisionVigente->id }}">
                            <button type="submit" class="certificado-modal__button certificado-modal__button--info">
                                <i class="fa-solid fa-paper-plane"></i>
                                Enviar al solicitante
                            </button>
                        </form>
                    @endif

                    @if ($puedeGestionarEmision && $anulacionDisponible)
                        <button type="button" class="certificado-modal__button certificado-modal__button--danger"
                            onclick="window.mostrarAnulacionCertificado('{{ $idModalCertificado }}')">
                            <i class="fa-solid fa-ban"></i>
                            Anular certificado
                        </button>
                    @elseif ($puedeGestionarEmision)
                        <span class="certificado-modal__pending">Anulación pendiente de configuración</span>
                    @endif
                </footer>
            </section>
        </div>
    @endif

    @if ($puedeGestionarEmision && ! $esDocumentoConsultable && $faltantesEmision->isNotEmpty())
        <div id="{{ $idModalValidacion }}" class="certificado-modal" hidden role="dialog" aria-modal="true"
            aria-labelledby="{{ $idModalValidacion }}-titulo" onclick="window.cerrarModalValidacionEmision('{{ $idModalValidacion }}', event)">
            <section class="certificado-modal__validation-card" onclick="event.stopPropagation()">
                <header class="certificado-modal__header">
                    <div>
                        <h2 id="{{ $idModalValidacion }}-titulo" class="certificado-modal__title">No se puede emitir el certificado</h2>
                        <p class="certificado-modal__subtitle">Revise lo siguiente antes de continuar.</p>
                    </div>
                    <button type="button" class="certificado-modal__close" aria-label="Cerrar"
                        onclick="window.cerrarModalValidacionEmision('{{ $idModalValidacion }}')">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </header>
                <div class="certificado-modal__validation-body">
                    <p>Faltan {{ $faltantesEmision->count() === 1 ? 'esta configuración' : 'estas configuraciones' }}:</p>
                    <ul>
                        @foreach ($faltantesEmision as $faltante)
                            <li><i class="fa-solid fa-circle-exclamation"></i>{{ $faltante }}</li>
                        @endforeach
                    </ul>
                </div>
            </section>
        </div>
    @endif
</div>

@once
    <style>
        .certificado-modal[hidden] { display: none; }
        .certificado-modal { align-items: center; background: rgba(15, 23, 42, .58); display: flex; inset: 0; justify-content: center; padding: 22px; position: fixed; z-index: 100; }
        .certificado-modal__card { background: #fff; border-radius: 14px; box-shadow: 0 24px 70px rgba(15, 23, 42, .35); display: flex; flex-direction: column; height: min(88vh, 940px); max-width: 1180px; overflow: hidden; width: min(100%, 1180px); }
        .certificado-modal__header, .certificado-modal__footer { align-items: center; display: flex; gap: 12px; justify-content: space-between; padding: 14px 18px; }
        .certificado-modal__header { border-bottom: 1px solid #e2e8f0; }
        .certificado-modal__title { color: #0f172a; font-size: 1rem; font-weight: 800; margin: 0; }
        .certificado-modal__subtitle { color: #64748b; font-size: .78rem; margin: 3px 0 0; }
        .certificado-modal__close { color: #64748b; font-size: 1.2rem; line-height: 1; padding: 6px; }
        .certificado-modal__content { background: #e2e8f0; display: flex; flex: 1; min-height: 0; }
        .certificado-modal__documents { background: #f8fafc; border-right: 1px solid #dbe3ef; display: flex; flex: 0 0 230px; flex-direction: column; gap: 7px; overflow: auto; padding: 14px; }
        .certificado-modal__documents-title { color: #475569; font-size: .72rem; font-weight: 800; text-transform: uppercase; }
        .certificado-modal__document { background: #fff; border: 1px solid #dbe3ef; border-radius: 8px; color: #334155; display: flex; flex-direction: column; gap: 2px; padding: 9px 10px; text-align: left; }
        .certificado-modal__document.is-active { background: #ecfdf5; border-color: #14b8a6; color: #047857; }
        .certificado-modal__document strong { font-size: .76rem; }
        .certificado-modal__document small { font-size: .7rem; }
        .certificado-modal__viewer { flex: 1; min-width: 0; }
        .certificado-modal__viewer iframe { background: #fff; border: 0; height: 100%; width: 100%; }
        .certificado-modal__footer { background: #fff; border-top: 1px solid #e2e8f0; flex-wrap: wrap; justify-content: flex-end; }
        .certificado-modal__footer form { margin: 0; }
        .certificado-modal__button { align-items: center; border-radius: 7px; display: inline-flex; font-size: .78rem; font-weight: 700; gap: 7px; justify-content: center; padding: 8px 11px; }
        .certificado-modal__button--secondary { background: #fff; border: 1px solid #cbd5e1; color: #334155; }
        .certificado-modal__button--info { background: #2563eb; color: #fff; }
        .certificado-modal__button--danger { background: #dc2626; color: #fff; }
        .certificado-modal__pending { color: #a16207; font-size: .72rem; font-weight: 700; }
        .certificado-modal__validation-card { background: #fff; border-radius: 14px; box-shadow: 0 24px 70px rgba(15, 23, 42, .35); max-width: 580px; overflow: hidden; width: min(100%, 580px); }
        .certificado-modal__validation-body { color: #334155; font-size: .88rem; padding: 20px; }
        .certificado-modal__validation-body p { margin: 0 0 12px; }
        .certificado-modal__validation-body ul { display: grid; gap: 9px; list-style: none; margin: 0; padding: 0; }
        .certificado-modal__validation-body li { align-items: flex-start; background: #fff7ed; border: 1px solid #fed7aa; border-radius: 8px; color: #9a3412; display: flex; gap: 8px; line-height: 1.45; padding: 10px; }
        .certificado-modal__validation-body li i { margin-top: 2px; }
        .certificado-modal__anulacion { padding: 22px; }
        .certificado-modal__warning { align-items: flex-start; background: #fff1f2; border: 1px solid #fecdd3; border-radius: 8px; color: #9f1239; display: flex; font-size: .84rem; gap: 9px; line-height: 1.45; margin-bottom: 15px; padding: 11px; }
        .certificado-modal__label { color: #334155; display: block; font-size: .82rem; font-weight: 800; margin-bottom: 6px; }
        .certificado-modal__anulacion textarea { border: 1px solid #cbd5e1; border-radius: 8px; box-sizing: border-box; color: #0f172a; font-size: .84rem; padding: 9px; resize: vertical; width: 100%; }
        .certificado-modal__anulacion-actions { display: flex; flex-wrap: wrap; gap: 9px; justify-content: flex-end; margin-top: 15px; }
        @media (max-width: 720px) { .certificado-modal { padding: 8px; } .certificado-modal__card { height: 94vh; } .certificado-modal__documents { flex-basis: 145px; padding: 8px; } .certificado-modal__footer { align-items: stretch; flex-direction: column; } .certificado-modal__footer form, .certificado-modal__button { width: 100%; } }
    </style>

    <script>
        window.abrirModalCertificado = function(idModal, indice) {
            const modal = document.getElementById(idModal);
            if (!modal) return;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
            const selector = modal.querySelector('[data-documento-index="' + indice + '"]');
            if (selector) window.seleccionarDocumentoCertificado(idModal, selector);
        };

        window.cerrarModalCertificado = function(idModal, event) {
            if (event && event.target !== event.currentTarget) return;
            const modal = document.getElementById(idModal);
            if (!modal) return;
            modal.hidden = true;
            document.body.style.overflow = '';
            window.mostrarPdfCertificado(idModal);
        };

        window.seleccionarDocumentoCertificado = function(idModal, selector) {
            const modal = document.getElementById(idModal);
            if (!modal || !selector) return;
            modal.querySelectorAll('[data-documento-selector]').forEach((item) => item.classList.toggle('is-active', item === selector));
            modal.querySelector('[data-documento-frame]').src = selector.dataset.visualizarUrl;
            modal.querySelector('[data-documento-descargar]').href = selector.dataset.descargarUrl;
            modal.querySelector('[data-documento-emision-id]')?.setAttribute('value', selector.dataset.emisionId);
            modal.querySelector('[data-panel-anulacion]').action = selector.dataset.anularUrl;
            modal.querySelector('[data-numero-anulacion]').textContent = selector.dataset.numero;
        };

        window.mostrarAnulacionCertificado = function(idModal) {
            const modal = document.getElementById(idModal);
            if (!modal) return;
            modal.querySelector('[data-documento-preview]').hidden = true;
            modal.querySelector('[data-documento-actions]').hidden = true;
            modal.querySelector('[data-panel-anulacion]').hidden = false;
            modal.querySelector('[name="form_motivo_anulacion"]')?.focus();
        };

        window.mostrarPdfCertificado = function(idModal) {
            const modal = document.getElementById(idModal);
            if (!modal) return;
            modal.querySelector('[data-documento-preview]').hidden = false;
            modal.querySelector('[data-documento-actions]').hidden = false;
            modal.querySelector('[data-panel-anulacion]').hidden = true;
        };

        window.abrirModalValidacionEmision = function(idModal) {
            const modal = document.getElementById(idModal);
            if (!modal) return;
            modal.hidden = false;
            document.body.style.overflow = 'hidden';
        };

        window.cerrarModalValidacionEmision = function(idModal, event) {
            if (event && event.target !== event.currentTarget) return;
            const modal = document.getElementById(idModal);
            if (!modal) return;
            modal.hidden = true;
            document.body.style.overflow = '';
        };
    </script>
@endonce

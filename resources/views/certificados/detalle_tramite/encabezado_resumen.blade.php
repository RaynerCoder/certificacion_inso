{{--
    Muestra el título, el resumen y la descarga del certificado.
    No registra ni modifica información.
--}}

<header class="tramite-header">
    <h1 class="tramite-title">Detalle y seguimiento del trámite</h1>

    @php
        $bandejaRetorno = \App\Support\BandejaTramite::retorno(request('bandeja'));
        $urlBandejaRetorno = route($bandejaRetorno[1]);
    @endphp
    <div style="display:flex;flex-wrap:wrap;align-items:center;gap:10px;">
    <a href="{{ $urlBandejaRetorno }}" class="tramite-btn tramite-btn-muted">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
        Volver
    </a>

    @if ($puedeConsultarCertificadoEmitido || ($puedeEmitirCertificado && $certificado->tipoCertificado?->plantillaActiva))
        @php
            $esEmisionPorLote = $certificado->tipoCertificado?->modo_emision === 'POR_LOTE';
        @endphp
        <a href="{{ $puedeConsultarCertificadoEmitido && ! $esEmisionPorLote
            ? route('certificados_descargar', ['certificado' => $certificado, 'visualizar' => 1])
            : route('certificados_emitir', ['certificado' => $certificado, 'bandeja' => request('bandeja', 'finalizados')]) }}"
            class="tramite-btn tramite-btn-emit">
            <i
                class="fa-solid {{ in_array($certificado->estado, ['EMITIDO', 'VENCIDO'], true) ? 'fa-eye' : 'fa-file-circle-check' }}"></i>
            {{ $esEmisionPorLote ? (in_array($certificado->estado, ['EMITIDO', 'VENCIDO'], true) ? 'Ver certificados' : 'Emitir certificado') : (in_array($certificado->estado, ['EMITIDO', 'VENCIDO'], true) ? 'Ver certificado emitido' : 'Emitir certificado') }}
        </a>
    @endif
    </div>
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
            <span class="tramite-summary-label">Gestionado por</span>
            <span class="tramite-summary-value">{{ $personaGestoraActual ? $nombrePersona($personaGestoraActual) : 'Sin gestor asignado' }}</span>
            <span class="tramite-summary-role">{{ $tipoGestorActual }}</span>
        </div>
    </article>

    <article class="tramite-summary-item">
        <i class="fa-regular fa-flag tramite-summary-icon"></i>
        <div>
            <span class="tramite-summary-label">Estado actual</span>
            <span
                class="tramite-pill tramite-status-chip {{ $certificado->estado === 'OBSERVADO' ? 'tramite-pill-danger' : ($certificado->estado === 'EN_REVISION' ? 'tramite-pill-warn' : 'tramite-pill-ok') }}">
                {{ $estadoTextoShow }}
            </span>
        </div>
    </article>

    @if ($resumenEvaluacionCalificaciones['aplica'])
        <article class="tramite-summary-item">
            <i class="fa-solid fa-graduation-cap tramite-summary-icon"></i>
            <div>
                <span class="tramite-summary-label">Resultado de evaluación</span>
                <span class="tramite-summary-value">
                    {{ match ($resumenEvaluacionCalificaciones['resultado']) {
                        'APROBADO' => 'Aprobado',
                        'PARTICIPACION' => 'Participación',
                        default => 'Pendiente',
                    } }}
                </span>
            </div>
        </article>
    @endif
</section>

@if ($puedeDescargarCertificado)
    {{-- Documento emitido: el contador corresponde a la cuenta que inició sesión. --}}
    <section
        class="mt-4 flex flex-col gap-4 rounded-xl border border-emerald-200 bg-white p-4 shadow-sm sm:flex-row sm:items-center sm:justify-between">
        <div class="flex items-start gap-3">
            <span
                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700">
                <i class="fa-regular fa-file-pdf" aria-hidden="true"></i>
            </span>
            <div>
                <h2 class="font-bold text-slate-900">Certificado disponible</h2>
                <p class="mt-1 text-sm text-slate-500">
                    @if ($resumenDescargas['limite'] === null)
                        {{ $resumenDescargas['realizadas'] }} descargas realizadas · Sin límite
                    @else
                        {{ $resumenDescargas['realizadas'] }} de {{ $resumenDescargas['limite'] }}
                        descargas utilizadas
                    @endif
                </p>
            </div>
        </div>

        @if ($resumenDescargas['disponible'])
            <a href="{{ route('certificados_descargar', $certificado) }}"
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-700 sm:w-auto">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
                Descargar certificado
            </a>
        @else
            <span
                class="inline-flex w-full items-center justify-center gap-2 rounded-lg bg-slate-100 px-4 py-2.5 text-sm font-bold text-slate-500 sm:w-auto">
                <i class="fa-solid fa-ban" aria-hidden="true"></i>
                Límite alcanzado
            </span>
        @endif
    </section>
@endif

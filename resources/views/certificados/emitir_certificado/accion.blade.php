@php
    $puedeGestionar = (auth()->user()?->puede('certificados.emitir') ?? false)
        && $emision->estaVigente();
@endphp

<div class="flex flex-wrap items-center gap-1 whitespace-normal text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <a href="{{ route('certificados_emitidos_descargar', ['certificado' => $emision->id_certificado, 'emision' => $emision->id, 'visualizar' => 1]) }}" target="_blank" rel="noopener"
        class="inline-flex items-center rounded-md bg-amber-500 px-2 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-amber-600">
        Ver PDF
    </a>

    @if ($puedeGestionar && $emision->estado === 'EMITIDO')
        <button type="button" class="inline-flex items-center rounded-md bg-blue-600 px-2 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-blue-700"
            onclick="window.abrirModalEnvioCertificado(this)"
            data-url="{{ route('certificados_enviar_solicitante', $emision->id_certificado) }}"
            data-emision="{{ $emision->id }}"
            data-numero="{{ $emision->numero_certificado }}">
                Enviar
        </button>
    @endif

    @if ($puedeGestionar)
        <button type="button" class="inline-flex items-center rounded-md bg-rose-600 px-2 py-1.5 text-[11px] font-semibold text-white shadow-sm hover:bg-rose-700"
            onclick="window.abrirModalAnulacionCertificado(this)"
            data-url="{{ route('certificados_emitidos_anular', ['certificado' => $emision->id_certificado, 'emision' => $emision->id]) }}"
            data-numero="{{ $emision->numero_certificado }}">
            Anular
        </button>
    @endif
</div>

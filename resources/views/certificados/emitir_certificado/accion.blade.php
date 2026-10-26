@php
    $puedeGestionar = (auth()->user()?->puede('certificados.emitir') ?? false)
        && $emision->estaVigente();
@endphp

<div class="flex flex-nowrap items-center gap-1.5 whitespace-nowrap text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <x-wire-button href="{{ route('certificados_emitidos_descargar', ['certificado' => $emision->id_certificado, 'emision' => $emision->id, 'visualizar' => 1]) }}" amber xs>
        Ver PDF
    </x-wire-button>

    @if ($puedeGestionar && $emision->estado === 'EMITIDO')
        <form action="{{ route('certificados_enviar_solicitante', $emision->id_certificado) }}" method="POST">
            @csrf
            <input type="hidden" name="form_id_certificado_emitido" value="{{ $emision->id }}">
            <x-wire-button type="submit" blue xs>
                Enviar al solicitante
            </x-wire-button>
        </form>
    @endif

    @if ($puedeGestionar)
        <form action="{{ route('certificados_emitidos_anular', ['certificado' => $emision->id_certificado, 'emision' => $emision->id]) }}" method="POST"
            onsubmit="return window.confirmarAnulacionCertificado(this, '{{ e($emision->numero_certificado) }}');">
            @csrf
            <input type="hidden" name="form_motivo_anulacion">
            <x-wire-button type="submit" red xs>
                Anular
            </x-wire-button>
        </form>
    @endif
</div>

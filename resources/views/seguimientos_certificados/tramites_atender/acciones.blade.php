{{-- Acciones de la bandeja interna: aqui el funcionario abre el tramite o revisa su historial. --}}
<div class="flex flex-wrap items-center gap-1.5 text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <x-wire-button href="{{ route('seguimientos_show', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" emerald xs>
        Revisar
    </x-wire-button>

    <x-wire-button href="{{ route('seguimientos_tramite_historial', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" blue xs>
        Seguimiento
    </x-wire-button>

    @if ($seguimiento->certificado?->puedeEmitirse())
        <x-wire-button href="{{ route('certificados_emitir', $seguimiento->certificado) }}" amber xs>
            Imprimir certificado
        </x-wire-button>
    @endif
</div>

{{-- Acciones de tramites finalizados: se consulta el cierre y se imprime el certificado. --}}
<div class="flex flex-wrap items-center gap-1.5 text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <x-wire-button href="{{ route('seguimientos_show', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" emerald xs>
        Ver tramite
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

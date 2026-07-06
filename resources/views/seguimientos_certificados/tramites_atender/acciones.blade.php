{{-- Acciones de la bandeja interna: aqui el funcionario abre el tramite o revisa su historial. --}}
<div class="flex flex-wrap items-center gap-2">
    <x-wire-button href="{{ route('seguimientos_show', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" emerald xs>
        Revisar
    </x-wire-button>

    <x-wire-button href="{{ route('seguimientos_tramite_historial', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" blue xs>
        Seguimiento
    </x-wire-button>

    @if ($seguimiento->certificado?->puedeEmitirse())
        <x-wire-button href="{{ route('certificados_emitir', $seguimiento->certificado) }}" amber xs>
            Emitir certificado
        </x-wire-button>
    @endif
</div>

{{-- Acciones de consulta general: permite revisar el tramite y su seguimiento institucional. --}}
<div class="flex flex-wrap items-center gap-2">
    <x-wire-button href="{{ route('seguimientos_show', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" emerald xs>
        Ver trámite
    </x-wire-button>

    <x-wire-button href="{{ route('seguimientos_tramite_historial', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" blue xs>
        Seguimiento
    </x-wire-button>
</div>

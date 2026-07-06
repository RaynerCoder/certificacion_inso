{{-- Acciones de la tabla "Mis tramites": el solicitante solo consulta su seguimiento. --}}
<div class="flex flex-wrap items-center gap-2">
    <x-wire-button href="{{ route('seguimientos_show', ['seguimiento' => $seguimiento, 'bandeja' => $bandeja]) }}" emerald xs>
        Ver trámite
    </x-wire-button>
</div>

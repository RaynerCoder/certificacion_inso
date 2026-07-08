{{-- Acciones de la tabla "Mis tramites": el solicitante solo consulta su seguimiento. --}}
<div class="flex flex-wrap items-center gap-1.5 text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <x-wire-button href="{{ route('certificados_show', ['certificado' => $seguimiento->certificado, 'bandeja' => $bandeja]) }}" emerald xs>
        Ver trámite
    </x-wire-button>
</div>

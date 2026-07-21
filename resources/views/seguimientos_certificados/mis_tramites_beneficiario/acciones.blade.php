{{-- Acciones de "Mis trámites": el solicitante ve su trámite y puede imprimir cuando ya fue aprobado. --}}
<div class="flex flex-wrap items-center gap-1.5 text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <x-wire-button href="{{ route('certificados_show', ['certificado' => $seguimiento->certificado, 'bandeja' => $bandeja]) }}" emerald xs>
        Ver trámite
    </x-wire-button>

    @if ($seguimiento->certificado?->estado === 'EMITIDO')
        <x-wire-button href="{{ route('certificados_emitir', ['certificado' => $seguimiento->certificado, 'bandeja' => 'enviadas']) }}" amber xs>
            Imprimir certificado
        </x-wire-button>
    @endif
</div>

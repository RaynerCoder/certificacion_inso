{{-- Acciones de "Mis trámites": el solicitante ve su trámite y puede imprimir cuando ya fue aprobado. --}}
<div class="flex flex-wrap items-center gap-1.5 text-[11px] leading-none [&_a]:!px-2 [&_a]:!py-1 [&_button]:!px-2 [&_button]:!py-1">
    <x-wire-button href="{{ route('certificados_show', ['certificado' => $seguimiento->certificado, 'bandeja' => $bandeja]) }}" emerald xs>
        Ver trámite
    </x-wire-button>

    @if (in_array($seguimiento->certificado?->estado, ['EMITIDO', 'VENCIDO'], true) && filled($seguimiento->certificado?->url_documento))
        <x-wire-button href="{{ route('certificados_descargar', ['certificado' => $seguimiento->certificado, 'visualizar' => 1]) }}" amber xs>
            Ver certificado
        </x-wire-button>
    @endif

    {{-- Permite retomar el pago cuando el trámite todavía no fue enviado a revisión. --}}
    @if ($seguimiento->certificado?->estado === 'PENDIENTE_PAGO')
        <x-wire-button href="{{ route('pagos_openbcb_mostrar_pago', ['certificado' => $seguimiento->certificado,]) }}" amber xs>
            Continuar pago
        </x-wire-button>
    @endif    
</div>

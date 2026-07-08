<div class="flex flex-wrap items-center gap-2">
    <x-wire-button href="{{ route('certificados_plantillas_show', $tipoCertificado) }}" emerald xs>
        Ver
    </x-wire-button>

    <x-wire-button href="{{ route('certificados_plantillas_edit', $tipoCertificado) }}" blue xs>
        Editar
    </x-wire-button>
</div>

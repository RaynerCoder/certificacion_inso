<div class="plantillas-certificado-acciones">
    <x-wire-button href="{{ route('certificados_plantillas_show', $tipoCertificado) }}" emerald xs>
        Ver
    </x-wire-button>

    <x-wire-button href="{{ route('certificados_plantillas_edit', $tipoCertificado) }}" blue xs>
        Editar
    </x-wire-button>

    @if ($plantillaCertificado)
        <form action="{{ route('certificados_plantillas_destroy', $plantillaCertificado) }}" method="POST"
            class="delete-form-plantilla m-0">
            @csrf
            @method('DELETE')
            <x-wire-button type="submit" red xs>
                Eliminar
            </x-wire-button>
        </form>
    @endif
</div>

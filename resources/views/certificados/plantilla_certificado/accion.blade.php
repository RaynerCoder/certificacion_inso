<div class="plantillas-certificado-acciones">
    <x-wire-button href="{{ route('certificados_plantillas_edit', $plantillaCertificado) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('certificados_plantillas_reutilizar', $plantillaCertificado) }}" method="POST" class="m-0">
        @csrf
        <x-wire-button type="submit" secondary xs>
            Reutilizar
        </x-wire-button>
    </form>

    <form action="{{ route('certificados_plantillas_destroy', $plantillaCertificado) }}" method="POST"
        class="delete-form-plantilla m-0">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>

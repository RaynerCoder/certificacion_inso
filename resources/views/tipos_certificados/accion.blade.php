<div class="flex items-center space-x-2">

    {{-- Abre el mapa de requisitos del tipo de certificado sin modificar el registro. --}}
    <x-wire-button href="{{ route('tipos_certificados_show', $tipoCertificado) }}" green xs>
        Ver
    </x-wire-button>

    {{-- Abre la vista completa de edicion para modificar tambien los requisitos asociados. --}}
    <x-wire-button href="{{ route('tipos_certificados_edit', $tipoCertificado) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('tipos_certificados_destroy', $tipoCertificado) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>

</div>

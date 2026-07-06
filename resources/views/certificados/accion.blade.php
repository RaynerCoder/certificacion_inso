<div class="flex items-center space-x-2">

    {{-- Ver --}}
    <x-wire-button href="{{ route('certificados_show', $certificado) }}" emerald xs>
        Ver
    </x-wire-button>

    {{-- Editar --}}
    <x-wire-button href="{{ route('certificados_edit', $certificado) }}" blue xs>
        Editar
    </x-wire-button>

    {{-- Eliminar --}}
    <form action="{{ route('certificados_destroy', $certificado) }}"
        method="POST"
        class="delete-form">

        @csrf
        @method('DELETE')

        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>

    </form>

</div>
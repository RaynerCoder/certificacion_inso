<div class="flex items-center space-x-2">

    {{-- Producto: abre el registro de producto usando esta persona/empresa como importador. --}}
    <x-wire-button href="{{ route('productos_create', ['form_id_importador_persona' => $persona->id]) }}" amber xs>
        Producto
    </x-wire-button>

    {{-- Ver --}}
    <x-wire-button href="{{ route('personas_show', $persona) }}" emerald xs>
        Ver
    </x-wire-button>

    {{-- Editar --}}
    <x-wire-button href="{{ route('personas_edit', $persona) }}" blue xs>
        Editar
    </x-wire-button>

    {{-- Eliminar --}}
    <form action="{{ route('personas_destroy', $persona) }}"
        method="POST"
        class="delete-form">

        @csrf
        @method('DELETE')

        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>

    </form>

</div>

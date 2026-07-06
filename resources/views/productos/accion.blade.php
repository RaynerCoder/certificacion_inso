<div class="flex items-center space-x-2">

    {{-- Ver --}}
    <x-wire-button href="{{ route('productos_show', $producto) }}" emerald xs>
        Ver
    </x-wire-button>

    {{-- Editar --}}
    <x-wire-button href="{{ route('productos_edit', $producto) }}" blue xs>
        Editar
    </x-wire-button>

    {{-- Eliminar --}}
    <form action="{{ route('productos_destroy', $producto) }}"
        method="POST"
        class="delete-form">

        @csrf
        @method('DELETE')

        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>

    </form>

</div>
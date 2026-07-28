<div class="flex items-center justify-center space-x-2">
    <x-wire-button type="button" blue xs
        data-editar-territorio
        data-id="{{ $territorio->id }}">
        Editar
    </x-wire-button>

    <form action="{{ route('territorios_destroy', $territorio) }}" method="POST" class="delete-form-territorio inline-flex">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>

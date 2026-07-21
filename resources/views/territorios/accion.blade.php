<div class="flex items-center space-x-2">
    <x-wire-button type="button" blue xs
        onclick='abrirModalEditarTerritorio(@js($territorio->id), @js($territorio->id_padre_territorio), @js($territorio->nombre), @js($territorio->codigo), @js($territorio->estado))'>
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

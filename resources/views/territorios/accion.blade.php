<div class="flex items-center space-x-2">

    <x-wire-button href="{{ route('territorios_edit', $territorio) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('territorios_destroy', $territorio) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>
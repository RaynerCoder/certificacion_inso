<div class="flex items-center space-x-2">

    <x-wire-button href="{{ route('fabricantes_edit', $fabricante) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('fabricantes_destroy', $fabricante) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>
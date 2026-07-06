<div class="flex items-center space-x-2">

    <x-wire-button href="{{ route('responsables_show', $responsable) }}" emerald xs>
        Ver
    </x-wire-button>

    <x-wire-button href="{{ route('responsables_edit', $responsable) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('responsables_destroy', $responsable) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>

<div class="roles-table-actions">

    <x-wire-button href="{{ route('roles_edit', $rol) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('roles_destroy', $rol) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>

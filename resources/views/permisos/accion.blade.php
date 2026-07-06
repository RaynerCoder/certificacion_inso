<div class="flex items-center space-x-2">

    <x-wire-button type="button" blue xs
        data-editar-permiso
        data-id="{{ $permiso->id }}"
        data-nombre="{{ $permiso->nombre }}"
        data-estado="{{ $permiso->estado }}">
        Editar
    </x-wire-button>

    <form action="{{ route('permisos_destroy', $permiso) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>

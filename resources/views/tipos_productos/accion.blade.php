<div class="flex items-center space-x-2">

    <x-wire-button href="{{ route('tipos_productos_edit', $tipo_producto) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('tipos_productos_destroy', $tipo_producto) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>


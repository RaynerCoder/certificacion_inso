<div class="flex items-center space-x-2">
    <x-wire-button type="button" blue xs
        data-tipo-producto-editar
        data-url="{{ route('tipos_productos_update', $tipo_producto) }}"
        data-descripcion="{{ $tipo_producto->descripcion }}"
        data-codigo="{{ $tipo_producto->codigo }}"
        data-estado="{{ $tipo_producto->estado }}">
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

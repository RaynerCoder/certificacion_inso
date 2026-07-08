<div class="flex items-center space-x-2">
    <x-wire-button type="button" blue xs
        data-fabricante-editar
        data-url="{{ route('fabricantes_update', $fabricante) }}"
        data-nombre="{{ $fabricante->nombre }}"
        data-descripcion="{{ $fabricante->descripcion }}"
        data-razon-social="{{ $fabricante->razon_social }}"
        data-estado="{{ $fabricante->estado }}">
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

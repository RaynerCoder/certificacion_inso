<div class="tabla-acciones">
    <x-wire-button type="button" blue xs
        data-editar-cargo
        data-id="{{ $cargo->id }}"
        data-nombre="{{ $cargo->nombre }}"
        data-descripcion="{{ $cargo->descripcion }}"
        data-id-area="{{ $cargo->id_area }}"
        data-estado="{{ $cargo->estado }}">
        Editar
    </x-wire-button>

    <form action="{{ route('cargos_destroy', $cargo) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>

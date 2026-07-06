<div class="tabla-acciones">
    <x-wire-button type="button" blue xs
        onclick='abrirModalEditarCargo(@js($cargo->id), @js($cargo->nombre), @js($cargo->descripcion), @js($cargo->id_area), @js($cargo->estado))'>
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

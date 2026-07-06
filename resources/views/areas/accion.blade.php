<div class="tabla-acciones">
    <x-wire-button type="button" blue xs
        onclick='abrirModalEditarArea(@js($area->id), @js($area->id_area_padre), @js($area->nombre), @js($area->descripcion), @js($area->estado))'>
        Editar
    </x-wire-button>

    <form action="{{ route('areas_destroy', $area) }}" method="POST" class="delete-form-area">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>

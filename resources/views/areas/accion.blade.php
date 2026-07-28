<div class="tabla-acciones">
    <x-wire-button type="button" green xs
        data-ver-area
        data-id="{{ $area->id }}"
        data-nombre="{{ $area->nombre }}"
        data-area-padre="{{ $area->areaPadre?->nombre ?: 'Sin area superior' }}"
        data-descripcion="{{ $area->descripcion ?: 'Sin descripcion' }}"
        data-cargos="{{ $area->cargos->pluck('nombre')->implode('|') }}"
        data-estado="{{ (string) $area->estado === '1' ? 'Activo' : 'Inactivo' }}">
        Ver
    </x-wire-button>

    <x-wire-button type="button" blue xs
        data-editar-area
        data-id="{{ $area->id }}"
        data-id-area-padre="{{ $area->id_area_padre }}"
        data-nombre="{{ $area->nombre }}"
        data-descripcion="{{ $area->descripcion }}"
        data-estado="{{ $area->estado }}">
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

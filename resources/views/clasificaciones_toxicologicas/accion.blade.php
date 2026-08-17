<div class="flex items-center space-x-2">
    <x-wire-button type="button" blue xs
        data-clasificacion-toxicologica-editar
        data-url="{{ route('clasificaciones_toxicologicas_update', $clasificacion_toxicologica) }}"
        data-descripcion="{{ $clasificacion_toxicologica->descripcion }}"
        data-codigo="{{ $clasificacion_toxicologica->codigo }}"
        data-estado="{{ $clasificacion_toxicologica->estado }}">
        Editar
    </x-wire-button>

    <form action="{{ route('clasificaciones_toxicologicas_destroy', $clasificacion_toxicologica) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>

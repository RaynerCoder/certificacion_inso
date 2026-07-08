<div class="flex items-center space-x-2">
    <x-wire-button type="button" blue xs
        data-ingrediente-editar
        data-url="{{ route('ingredientes_update', $ingrediente) }}"
        data-nombre="{{ $ingrediente->nombre }}"
        data-composicion="{{ $ingrediente->composicion }}"
        data-riesgo-salud="{{ $ingrediente->riesgo_salud }}"
        data-estado="{{ $ingrediente->estado }}">
        Editar
    </x-wire-button>

    <form action="{{ route('ingredientes_destroy', $ingrediente) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
</div>

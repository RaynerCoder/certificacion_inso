<div class="flex items-center space-x-2">

    <!-- El boton esta en modo escucha en JavaScript -->
    <x-wire-button type="button" blue xs
        data-editar-tipo-evidencia
        data-id="{{ $tipoEvidencia->id }}"
        data-codigo="{{ $tipoEvidencia->codigo }}"
        data-nombre="{{ $tipoEvidencia->nombre }}"
        data-descripcion="{{ $tipoEvidencia->descripcion }}"
        data-tamanio-maximo-mb="{{ $tipoEvidencia->tamanio_maximo_mb }}"
        data-estado="{{ $tipoEvidencia->estado }}">
        Editar
    </x-wire-button>

    <form action="{{ route('tipos_evidencias_destroy', $tipoEvidencia) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>

</div>

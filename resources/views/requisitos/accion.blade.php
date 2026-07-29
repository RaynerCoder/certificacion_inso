<div class="flex flex-wrap items-center justify-center gap-1.5 sm:flex-nowrap">

    @include('requisitos.uso', ['requisito' => $requisito])

    <!-- El boton esta en modo escucha en JavaScript -->
    <x-wire-button type="button" blue xs 
        data-editar-requisito 
        data-id="{{ $requisito->id }}"
        data-descripcion="{{ $requisito->descripcion }}" 
        data-estado="{{ $requisito->estado }}">
        Editar
    </x-wire-button>

    <form action="{{ route('requisitos_destroy', $requisito) }}" method="POST" class="delete-form m-0">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>

</div>

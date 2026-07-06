<div class="flex items-center space-x-2">

    <x-wire-button href="{{ route('empresas_edit', $empresa) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('empresas_destroy', $empresa) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>
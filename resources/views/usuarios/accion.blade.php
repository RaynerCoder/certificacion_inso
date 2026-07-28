<div class="flex items-center gap-2 whitespace-nowrap">

    <x-wire-button href="{{ route('usuarios_show', $usuario) }}" emerald xs>
        Ver
    </x-wire-button>

    <x-wire-button href="{{ route('usuarios_edit', $usuario) }}" blue xs>
        Editar
    </x-wire-button>

    @unless ($usuario->esSuperAdministrador())
        <form action="{{ route('usuarios_destroy', $usuario) }}" method="POST" class="delete-form">
            @csrf
            @method('DELETE')
            <x-wire-button type="submit" red xs>
                Eliminar
            </x-wire-button>
        </form>
    @endunless
    
</div>

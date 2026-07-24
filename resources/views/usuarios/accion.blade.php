<div class="flex items-center space-x-2">

    <x-wire-button href="{{ route('usuarios_edit', $usuario) }}" blue xs>
        Editar
    </x-wire-button>

    @unless ($usuario->esSuperAdministrador() || (string) $usuario->estado === '0')
        <form action="{{ route('usuarios_destroy', $usuario) }}" method="POST" class="delete-form">
            @csrf
            @method('DELETE')
            <x-wire-button type="submit" red xs>
                Inactivar
            </x-wire-button>
        </form>
    @endunless
    
</div>

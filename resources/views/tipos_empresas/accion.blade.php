<div class="flex items-center space-x-2">

    <x-wire-button href="{{ route('tipos_empresas_edit', $tipo_empresa) }}" blue xs>
        Editar
    </x-wire-button>

    <form action="{{ route('tipos_empresas_destroy', $tipo_empresa) }}" method="POST" class="delete-form">
        @csrf
        @method('DELETE')
        <x-wire-button type="submit" red xs>
            Eliminar
        </x-wire-button>
    </form>
    
</div>
<x-admin-layout title="Editar Usuario | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Usuarios',
        'href' => route('usuarios_index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    @include('seguridad.estilos')

    <form action="{{ route('usuarios_update', $usuario) }}" method="POST" class="seg-page" autocomplete="off">
        @csrf
        @method('PUT')

        @include('usuarios.formulario', [
            'modo' => 'editar',
            'usuario' => $usuario,
            'cargosSeleccionados' => collect(old('form_cargos', $usuario->funcionario?->cargos->pluck('id')->all() ?? []))->map(fn ($id) => (int) $id)->all(),
            'rolesSeleccionados' => collect(old('form_roles', $usuario->roles->pluck('id')->all()))->map(fn ($id) => (int) $id)->all(),
            'permisosSeleccionados' => collect(old('form_permisos', $usuario->permisosDirectos->pluck('id')->all()))->map(fn ($id) => (int) $id)->all(),
        ])
    </form>

</x-admin-layout>

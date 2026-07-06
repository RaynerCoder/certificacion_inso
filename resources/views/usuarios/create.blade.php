<x-admin-layout title="Nuevo Usuario | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Usuarios',
        'href' => route('usuarios_index'),
    ],
    [
        'name' => 'Crear',
    ],
]">

    @include('seguridad.estilos')

    <form action="{{ route('usuarios_store') }}" method="POST" class="seg-page" autocomplete="off">
        @csrf

        @include('usuarios.formulario', [
            'modo' => 'crear',
            'usuario' => null,
            'cargosSeleccionados' => collect(old('form_cargos', []))->map(fn ($id) => (int) $id)->all(),
            'rolesSeleccionados' => collect(old('form_roles', []))->map(fn ($id) => (int) $id)->all(),
            'permisosSeleccionados' => collect(old('form_permisos', []))->map(fn ($id) => (int) $id)->all(),
        ])
    </form>

</x-admin-layout>

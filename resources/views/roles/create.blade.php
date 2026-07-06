<x-admin-layout title="Nuevo Rol | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Roles',
        'href' => route('roles_index'),
    ],
    [
        'name' => 'Crear',
    ],
]">

    @include('seguridad.estilos')

    <form action="{{ route('roles_store') }}" method="POST" class="seg-page" autocomplete="off">
        @csrf

        @include('roles.formulario', [
            'modo' => 'crear',
            'rol' => null,
            'permisosSeleccionados' => collect(old('form_permisos', []))->map(fn ($id) => (int) $id)->all(),
        ])
    </form>

</x-admin-layout>

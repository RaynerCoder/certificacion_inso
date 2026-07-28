<x-admin-layout title="Editar Rol | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Roles',
        'href' => route('roles_index'),
    ],
    [
        'name' => 'Editar',
    ],
]">

    @include('seguridad.estilos')
    @include('roles.estilo')

    <form action="{{ route('roles_update', $rol) }}" method="POST" class="seg-page roles-module" autocomplete="off">
        @csrf
        @method('PUT')

        @include('roles.formulario', [
            'modo' => 'editar',
            'rol' => $rol,
            'permisosSeleccionados' => collect(old('form_permisos', $rol->permisos->pluck('id')->all()))->map(fn ($id) => (int) $id)->all(),
        ])
    </form>

</x-admin-layout>

<x-admin-layout title="Usuarios | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Usuarios',
        'href' => route('usuarios_index'),
    ],
]">

    @include('seguridad.estilos')

    <x-slot name="action">
        <x-wire-button href="{{ route('usuarios_create') }}" blue>
            Nuevo usuario
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/UsuarioTable.php --}}
    @livewire('datatables.usuario-table')

    @push('js')
        <script>
            // Confirma la baja sin borrar el historial ni las relaciones del usuario.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Inactivar usuario',
                    text: 'El usuario conservará su historial, pero ya no quedará activo.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Sí, inactivar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        formulario.submit();
                    }
                });
            });
        </script>
    @endpush

</x-admin-layout>

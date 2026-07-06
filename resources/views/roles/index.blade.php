<x-admin-layout title="Roles | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Roles',
        'href' => route('roles_index'),
    ],
]">

    @include('seguridad.estilos')

    <x-slot name="action">
        <x-wire-button href="{{ route('roles_create') }}" blue>
            Nuevo rol
        </x-wire-button>
    </x-slot>

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/RolTable.php --}}
    @livewire('datatables.rol-table')

    @push('js')
        <script>
            // Confirma con SweetAlert antes de eliminar un rol y sus relaciones.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar rol',
                    text: 'Se quitaran tambien sus permisos y asignaciones a usuarios.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#dc2626',
                    cancelButtonColor: '#64748b',
                    confirmButtonText: 'Si, eliminar',
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

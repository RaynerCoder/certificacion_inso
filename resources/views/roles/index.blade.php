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
    @include('roles.estilo')

    <x-slot name="action">
        <x-wire-button href="{{ route('roles_create') }}" blue>
            Nuevo rol
        </x-wire-button>
    </x-slot>

    <div class="roles-module">
        @if (session('error'))
            <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- Mantiene las columnas legibles y habilita desplazamiento solo en pantallas pequeñas. --}}
        <div class="roles-table-scroll">
            @livewire('datatables.rol-table')
        </div>
    </div>

    @push('js')
        <script>
            // Confirma el cambio antes de enviar la solicitud.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Eliminar rol',
                    text: 'El estado del rol cambiará a Inactivo si no está relacionado con otros datos.',
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

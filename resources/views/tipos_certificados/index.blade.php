<x-admin-layout title="Tipos de Certificado | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => '',
    ],
    [
        'name' => 'Tipos de Certificado',
        'href' => route('tipos_certificados_index'),
    ],
]">

    <x-slot name="action">
        <x-wire-button href="{{ route('tipos_certificados_create') }}" blue>
            Nuevo
        </x-wire-button>
    </x-slot>



    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/TipoCertificadoTable.php --}}
    @livewire('datatables.tipo-certificado-table')

    @push('js')
        <script>
            // Confirma eliminacion incluso cuando Livewire vuelve a renderizar la tabla.
            document.addEventListener('submit', function(e) {
                const formulario = e.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: 'Estas seguro?',
                    text: 'No podras revertir esta accion.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
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

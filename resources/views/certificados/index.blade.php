<x-admin-layout title="Certificado | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => '',
    ],
    [
        'name' => 'Emisión de Certificados',
        'href' => route('certificados_index'),
    ],
]">

    <x-slot name="action">
        <x-wire-button href="{{ route('certificados_create') }}" blue>
            Registrar
        </x-wire-button>
    </x-slot>



    {{-- Tabla principal del CRUD. La logica esta en app/Livewire/Datatables/CertificadoTable.php --}}
    @livewire('datatables.certificado-table')

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

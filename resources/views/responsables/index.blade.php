<x-admin-layout title="Responsables de Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Responsables de Empresas',
        'href' => route('responsables_index'),
    ],
]">

    {{-- <x-slot name="action">
        <x-wire-button href="{{ route('responsables_create') }}" emerald>
            Asignar Responsable a Empresa
        </x-wire-button>
    </x-slot> --}}

    {{-- Tabla principal del módulo: app/Livewire/Datatables/ResponsableTable.php --}}
    @livewire('datatables.responsable-table')

    @push('js')
        <script>
            // Confirma la eliminación para evitar quitar responsables por accidente.
            document.querySelectorAll('.delete-form').forEach(elemento => {
                elemento.addEventListener('submit', function (e) {
                    e.preventDefault();

                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: 'Se eliminará la asignación del responsable a la empresa.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#059669',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            elemento.submit();
                        }
                    });
                });
            });
        </script>
    @endpush

</x-admin-layout>

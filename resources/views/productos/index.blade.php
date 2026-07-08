<x-admin-layout title="Productos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Gestión de Productos',
        'href' => '#',
    ],
    [
        'name' => 'Productos',
        'href' => route('productos_index'),
    ],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('productos_create') }}" blue>
            Nuevo producto
        </x-wire-button>
    </x-slot>

    {{-- Listado principal: las columnas se configuran en app/Livewire/Datatables/ProductoTable.php. --}}
    @livewire('datatables.producto-table')

    @push('js')
        <script>
            document.querySelectorAll('.delete-form').forEach((formulario) => {
                formulario.addEventListener('submit', function (evento) {
                    evento.preventDefault();

                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: '¡No podrás revertir esta acción!',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '¡Sí, eliminar!',
                        cancelButtonText: 'Cancelar',
                    }).then((resultado) => {
                        if (resultado.isConfirmed) {
                            formulario.submit();
                        }
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>

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
            // Se escucha el documento porque Livewire puede volver a dibujar las filas de la tabla.
            document.addEventListener('submit', function (evento) {
                const formulario = evento.target.closest('.delete-form');

                if (!formulario) {
                    return;
                }

                evento.preventDefault();

                Swal.fire({
                    title: '¿Está seguro?',
                    text: 'El producto se marcará como inactivo y dejará de aparecer en el listado.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, eliminar',
                    cancelButtonText: 'Cancelar',
                }).then((resultado) => {
                    if (resultado.isConfirmed) {
                        formulario.submit();
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>

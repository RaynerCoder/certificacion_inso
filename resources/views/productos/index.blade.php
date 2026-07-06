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


    <!-- Es la ruta para la tabla de tipos de productos en la direccion: app/Livewire/Datatables/ProductoTable.php -->
    @livewire('datatables.producto-table')


    <!-- Antes de hacer un push se agrego un stack('js') en resources/views/layouts/admin.blade.php -->
    <!-- Sirve para incluir scripts adicionales en las vistas -->
    @push('js')
        <script>
            forms = document.querySelectorAll('.delete-form');
            forms.forEach(elemento => {
                elemento.addEventListener('submit', function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: '¿Estás seguro?',
                        text: "¡No podrás revertir esta acción!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: '¡Sí, elimínar!',
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

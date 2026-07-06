<x-admin-layout title="Territorios | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Territorios',
        'href' => route('territorios_index'),
    ],
]">


    <x-slot name="action">
        <x-wire-button href="{{ route('territorios_create') }}" blue>
            Nuevo
        </x-wire-button>
    </x-slot>


    <!-- Es la ruta para la tabla de tipos de productos en la direccion: app/Livewire/Datatables/TerritorioTable.php -->
    @livewire('datatables.territorio-table')


    <!-- Antes de hacer un push se agrego un stack('js') en resources/views/layouts/admin.blade.php -->
    <!-- Sirve para incluir scripts adicionales en las vistas que extienden este layout -->
    @push('js')
        <script>
            forms = document.querySelectorAll('.delete-form');
            forms.forEach(elemento => {
                elemento.addEventListener('submit', function (e) {
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
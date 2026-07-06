<x-admin-layout title="Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Empresas',
        'href' => route('empresas_index'),
    ],
]">

    <x-slot name="action">
        <x-wire-button href="{{ route('empresas_create') }}" blue>
            Nuevo
        </x-wire-button>
    </x-slot>


    <!-- Es la ruta para la tabla de fabricantes en la direccion: app/Livewire/Datatables/EmpresaTable.php -->
    @livewire('datatables.empresa-table')


    <!-- Antes de hacer un push se agrego un stack('js') en resources/views/layouts/admin.blade.php -->
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
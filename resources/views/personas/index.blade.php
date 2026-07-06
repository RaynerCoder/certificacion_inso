<x-admin-layout title="Personas y Empresas | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Personas y Empresas',
        'href' => '#',
    ],
    [
        'name' => 'Listado',
        'href' => route('personas_index'),
    ],
]">


    <x-slot name="action">
        <x-wire-button href="{{ route('personas_create') }}" blue>
            Nuevo Solicitante
        </x-wire-button>
    </x-slot>


    <!-- Es la ruta para la tabla de tipos de productos en la direccion: app/Livewire/Datatables/PersonaTable.php -->
    @livewire('datatables.persona-table')


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

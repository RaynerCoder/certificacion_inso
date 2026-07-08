<x-admin-layout title="Registros y Productos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Registros y Productos',
        'href' => route('registros_index'),
    ],
]">

    <!-- Es la ruta para la tabla registros en la direccion: app/Livewire/Datatables/RegistrosTable.php -->
    @livewire('datatables.registro-table')


</x-admin-layout>
<x-admin-layout title="Presentaciones | Certificador" :breadcrumbs="[
    [
        'name' => 'Menú',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Presentaciones',
        'href' => route('presentaciones_index'),
    ],
]">

    <!-- Es la ruta para la tabla presentaciones en la direccion: app/Livewire/Datatables/PresentacionesTable.php -->
    @livewire('datatables.presentacion-table')

</x-admin-layout>
<x-admin-layout title="Tramitadores | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Personas y Empresas',
        'href' => '#',
    ],
    [
        'name' => 'Tramitadores',
        'href' => route('tramitadores_index'),
    ],
]">

    <x-slot name="action">
        <x-wire-button href="{{ route('tramitadores_create') }}" blue>
            Nuevo Tramitador
        </x-wire-button>
    </x-slot>

    {{-- Tabla principal del modulo: app/Livewire/Datatables/TramitadorTable.php --}}
    @livewire('datatables.tramitador-table')

</x-admin-layout>

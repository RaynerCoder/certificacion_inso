<x-admin-layout title="Plantillas de Certificado | Certificador" :breadcrumbs="[
    ['name' => 'Menu', 'href' => route('admin_dashboard')],
    ['name' => 'Certificados', 'href' => route('certificados_index')],
    ['name' => 'Plantillas', 'href' => route('certificados_plantillas_index')],
]">
    <x-slot name="action">
        <x-wire-button href="{{ route('certificados_plantillas_create') }}" blue>
            Nueva plantilla
        </x-wire-button>
    </x-slot>

    {{-- Tabla principal del modulo. La logica esta en app/Livewire/Datatables/PlantillaCertificadoTable.php --}}
    @livewire('datatables.plantilla-certificado-table')
</x-admin-layout>

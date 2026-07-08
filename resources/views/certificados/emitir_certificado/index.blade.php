<x-admin-layout title="Certificados emitidos | Certificador" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => 'Certificados',
        'href' => route('certificados_index'),
    ],
    [
        'name' => 'Certificados emitidos',
        'href' => route('certificados_emitidos_index'),
    ],
]">

    <x-slot name="action">
        <div class="flex flex-wrap items-center gap-2">
            <x-wire-button href="{{ route('certificados_index') }}" secondary>
                Volver
            </x-wire-button>
        </div>
    </x-slot>

    @livewire('datatables.certificado-table', ['estado' => 'EMITIDO'])

</x-admin-layout>

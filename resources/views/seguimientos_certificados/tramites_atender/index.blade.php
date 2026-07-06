<x-admin-layout :title="$tituloPagina . ' | Certificador'" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => $tituloPagina,
        'href' => route('seguimientos_index'),
    ],
]">
    @php
        // Bandeja usada por la tabla para traer solo los tramites asignados al usuario.
        $bandeja = 'recibidas';
    @endphp

    @include('seguimientos_certificados.estilos.bandejas')

    @if ($bandeja !== 'todos')
        <x-slot name="action">
            <x-wire-button href="{{ route('seguimientos_create') }}" blue>
                Nuevo tramite
            </x-wire-button>
        </x-slot>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <section class="solicitudes-panel">
        <div class="solicitudes-panel-body {{ $bandeja === 'recibidas' ? 'is-inbox-table' : '' }}">
            @livewire('datatables.seguimiento-table', ['bandeja' => $bandeja], key('seguimientos-' . $bandeja))
        </div>
    </section>
</x-admin-layout>

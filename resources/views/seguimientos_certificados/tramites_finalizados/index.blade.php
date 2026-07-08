<x-admin-layout :title="$tituloPagina . ' | Certificador'" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => $tituloPagina,
        'href' => route('seguimientos_finalizados'),
    ],
]">
    @php
        // Bandeja exclusiva para tramites cerrados.
        // La consulta y columnas estan en app/Livewire/Datatables/SeguimientoTable.php.
        $bandeja = 'finalizados';
    @endphp

    @include('seguimientos_certificados.estilos.bandejas')

    @if (session('error'))
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
            {{ session('error') }}
        </div>
    @endif

    <section class="solicitudes-panel">
        <div class="solicitudes-panel-body is-follow-table">
            @livewire('datatables.seguimiento-table', ['bandeja' => $bandeja], key('seguimientos-' . $bandeja))
        </div>
    </section>
</x-admin-layout>

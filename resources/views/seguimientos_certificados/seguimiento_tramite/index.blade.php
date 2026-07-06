<x-admin-layout :title="$tituloPagina . ' | Certificador'" :breadcrumbs="[
    [
        'name' => 'Menu',
        'href' => route('admin_dashboard'),
    ],
    [
        'name' => $tituloPagina,
        'href' => route('seguimientos_todos'),
    ],
]">
    @php
        // Pantalla: consulta general de tramites para seguimiento institucional.
        // No crea ni atiende; solo muestra la tabla Livewire en modo lectura.
        $bandeja = 'todos';
        $panelIcono = 'fa-solid fa-magnifying-glass-chart';
        $panelClase = 'is-inbox';
        $panelEtiqueta = 'Solo lectura';
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
        <div class="solicitudes-panel-head">
            <div class="solicitudes-panel-title">
                <span class="solicitudes-panel-icon {{ $panelClase }}">
                    <i class="{{ $panelIcono }}"></i>
                </span>
                <div>
                    <h2>{{ $tituloPagina }}</h2>
                    <p>{{ $descripcionPagina }}</p>
                </div>
            </div>

            <span class="solicitudes-panel-badge {{ $panelClase }}">{{ $panelEtiqueta }}</span>
        </div>

        <div class="solicitudes-panel-body {{ $bandeja === 'recibidas' ? 'is-inbox-table' : '' }}">
            @livewire('datatables.seguimiento-table', ['bandeja' => $bandeja], key('seguimientos-' . $bandeja))
        </div>
    </section>
</x-admin-layout>

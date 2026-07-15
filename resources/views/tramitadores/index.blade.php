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

    @push('js')
        <script>
            document.querySelectorAll('[data-tramitador-baja]').forEach((formulario) => {
                formulario.addEventListener('submit', (evento) => {
                    evento.preventDefault();

                    const nombre = formulario.dataset.tramitadorNombre;
                    const pendientes = Number(formulario.dataset.tramitesPendientes || 0);
                    const detalle = pendientes
                        ? `Tiene ${pendientes} tramite(s) pendiente(s). Se transferiran al beneficiario.`
                        : 'No tiene tramites pendientes de correccion.';

                    Swal.fire({
                        title: `Dar de baja a ${nombre}`,
                        text: detalle,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Confirmar baja',
                        cancelButtonText: 'Cancelar',
                    }).then((resultado) => {
                        if (!resultado.isConfirmed) {
                            return;
                        }

                        formulario.querySelector('button[type="submit"]')?.setAttribute('disabled', 'disabled');
                        formulario.submit();
                    });
                });
            });
        </script>
    @endpush
</x-admin-layout>

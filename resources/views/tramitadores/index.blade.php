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
        @if ($empresa)
            @permiso('tramitadores.ver')
                <x-wire-button href="{{ route('tramitadores_create') }}" class="w-full justify-center sm:w-auto" blue>
                    Asignar tramitador
                </x-wire-button>
            @endpermiso
        @endif
    </x-slot>

    {{-- Tabla principal del modulo: app/Livewire/Datatables/TramitadorTable.php --}}
    <div class="min-w-0 max-w-full overflow-x-auto">
        @livewire('datatables.tramitador-table')
    </div>

    @push('js')
        <script>
            document.addEventListener('submit', (evento) => {
                const formulario = evento.target.closest('[data-tramitador-baja]');

                if (! formulario) {
                    return;
                }

                evento.preventDefault();

                const nombre = formulario.dataset.tramitadorNombre;
                const empresa = formulario.dataset.tramitadorEmpresa;
                const pendientes = Number(formulario.dataset.tramitesPendientes || 0);
                const detalle = pendientes
                    ? `Tiene ${pendientes} trámite(s) pendiente(s), que se transferirán al beneficiario.`
                    : 'No tiene trámites pendientes de corrección.';

                Swal.fire({
                    title: `Dar de baja a ${nombre}`,
                    text: `Dejará de representar a ${empresa}. ${detalle}`,
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
        </script>
    @endpush
</x-admin-layout>
